<?php

use App\Models\Notification;
use App\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

function supportUser(string $role): User
{
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole($role);

    if ($role === 'designer') {
        $user->designerProfile()->create(['full_name' => $user->name, 'approval_status' => 'approved']);
    }

    if ($role === 'print_provider') {
        $user->printProvider()->create(['company_name' => 'مطبعة', 'approval_status' => 'approved', 'is_active' => true]);
    }

    return $user;
}

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->admin = supportUser('admin');
});

it('lets a customer open a ticket, the admin answer it, and the customer reply in the same thread', function () {
    $customer = supportUser('customer');

    $created = $this->actingAs($customer)->postJson(route('customer.support.store'), [
        'category' => 'مشكلة تقنية', 'subject' => 'مشكلة في انشاء حساب', 'message' => 'لا أستطيع إكمال التسجيل أبدًا.',
    ])->assertCreated();

    $ticket = SupportTicket::firstOrFail();
    expect($ticket->user_id)->toBe($customer->id)->and($ticket->status)->toBe(SupportTicket::STATUS_PENDING_ADMIN);
    expect(Notification::where('user_id', $this->admin->id)->where('type', 'support.new')->count())->toBe(1);

    $this->actingAs($this->admin)->get(route('admin.support'))->assertOk()->assertSee('مشكلة في انشاء حساب');

    $this->actingAs($this->admin)->postJson(route('admin.support.reply', $ticket), ['message' => 'جرّب من جديد.'])
        ->assertOk()->assertJsonPath('ticket.status', 'open');

    $note = Notification::where('user_id', $customer->id)->where('type', 'support.reply')->firstOrFail();
    expect($note->link)->toBe(route('customer.support.show', $ticket));

    $this->actingAs($customer)->get($note->link)->assertOk()->assertSee($ticket->ticket_number)->assertSee('مشكلة في انشاء حساب');
    $this->actingAs($customer)->get(route('customer.support'))->assertOk()->assertSee(route('customer.support.show', $ticket));

    $this->actingAs($customer)->postJson(route('customer.support.reply', $ticket), ['message' => 'ما زالت المشكلة.'])
        ->assertOk()->assertJsonPath('ticket.status', 'new')->assertJsonCount(3, 'ticket.messages');

    expect($ticket->fresh()->status)->toBe(SupportTicket::STATUS_PENDING_ADMIN);
    expect(Notification::where('user_id', $this->admin->id)->where('title', 'رد جديد على طلب دعم')->count())->toBe(1);

    $this->actingAs($this->admin)->getJson(route('admin.support.messages', $ticket))
        ->assertOk()->assertJsonPath('ticket.messages.2.text', 'ما زالت المشكلة.')->assertJsonPath('ticket.messages.1.fromAdmin', true);
});

it('keeps a ticket private to its owner', function () {
    $customer = supportUser('customer');
    $other = supportUser('customer');
    $this->actingAs($customer)->postJson(route('customer.support.store'), [
        'category' => 'مشكلة تقنية', 'subject' => 'عنوان الطلب', 'message' => 'تفاصيل المشكلة الكاملة هنا.',
    ]);
    $ticket = SupportTicket::firstOrFail();

    $this->actingAs($other)->get(route('customer.support.show', $ticket))->assertNotFound();
    $this->actingAs($other)->getJson(route('customer.support.messages', $ticket))->assertNotFound();
    $this->actingAs($other)->postJson(route('customer.support.reply', $ticket), ['message' => 'مرحبا'])->assertNotFound();
});

it('closes a ticket as solved and stops further replies', function () {
    $customer = supportUser('customer');
    $this->actingAs($customer)->postJson(route('customer.support.store'), [
        'category' => 'مشكلة تقنية', 'subject' => 'عنوان الطلب', 'message' => 'تفاصيل المشكلة الكاملة هنا.',
    ]);
    $ticket = SupportTicket::firstOrFail();

    $this->actingAs($this->admin)->postJson(route('admin.support.status', $ticket), ['status' => 'resolved'])
        ->assertOk()->assertJsonPath('ticket.closed', true);

    expect($ticket->fresh()->closed_at)->not->toBeNull();
    expect(Notification::where('user_id', $customer->id)->where('title', 'تم حل طلب الدعم')->exists())->toBeTrue();

    $this->actingAs($customer)->postJson(route('customer.support.reply', $ticket), ['message' => 'سؤال إضافي'])->assertUnprocessable();
});

it('gives designers and print shops the same conversation', function (string $role, string $routePrefix, string $roleKey) {
    $user = supportUser($role);
    $category = $role === 'designer' ? 'designs' : 'orders';

    $this->actingAs($user)->post(route($routePrefix.'.support.store'), [
        'category' => $category, 'subject' => 'عنوان الطلب', 'message' => 'تفاصيل المشكلة الكاملة هنا.', 'reference' => 'ORD-1',
    ])->assertRedirect();

    $ticket = SupportTicket::firstOrFail();
    expect($ticket->messages()->first()->message)->toContain('ORD-1');

    $this->actingAs($user)->get(route($routePrefix.'.support'))->assertOk()->assertSee(route($routePrefix.'.support.show', $ticket));
    $this->actingAs($user)->get(route($routePrefix.'.support.show', $ticket))->assertOk()->assertSee($ticket->ticket_number);

    $this->actingAs($this->admin)->postJson(route('admin.support.reply', $ticket), ['message' => 'وصلنا طلبك.'])->assertOk();
    $note = Notification::where('user_id', $user->id)->where('type', 'support.reply')->firstOrFail();
    expect($note->link)->toBe(route($routePrefix.'.support.show', $ticket));

    $this->actingAs($user)->getJson(route($routePrefix.'.support.messages', $ticket))->assertOk()->assertJsonCount(2, 'ticket.messages');
    $this->actingAs($user)->postJson(route($routePrefix.'.support.reply', $ticket), ['message' => 'شكرًا'])->assertOk();

    $this->actingAs($this->admin)->get(route('admin.support'))->assertOk()->assertSee($roleKey);
})->with([
    'designer' => ['designer', 'designer', 'مصمم'],
    'print shop' => ['print_provider', 'print-provider', 'مطبعة'],
]);

it('opens a ticket on its own chat page for the admin, and old ?open links go there', function () {
    $customer = supportUser('customer');
    $this->actingAs($customer)->postJson(route('customer.support.store'), [
        'category' => 'مشكلة تقنية', 'subject' => 'مشكلة في الدفع', 'message' => 'لم يتم خصم المبلغ بشكل صحيح.',
    ]);
    $ticket = SupportTicket::firstOrFail();

    $this->actingAs($this->admin)->get(route('admin.support.show', $ticket))
        ->assertOk()->assertSee('مشكلة في الدفع')->assertSee($customer->email);
    $this->actingAs($this->admin)->get(route('admin.support', ['open' => $ticket->id]))->assertRedirect(route('admin.support.show', $ticket));

    expect(Notification::where('user_id', $this->admin->id)->value('link'))->toBe(route('admin.support.show', $ticket));
});

it('shows a friendly empty state when there are no tickets', function () {
    $this->actingAs($this->admin)->get(route('admin.support'))
        ->assertOk()->assertSee('لا توجد طلبات دعم بعد')->assertDontSee('جدول المشاكل الواردة');
});

it('reads the ticket notifications when the ticket is opened, for the user and for the admin', function () {
    $customer = supportUser('customer');
    $ticket = \App\Support\SupportTickets::create($customer, 'مشكلة تقنية', 'عنوان الطلب', 'تفاصيل المشكلة الكاملة هنا.');
    \App\Support\SupportTickets::replyAsAdmin($ticket, $this->admin, 'جرّب مرة ثانية.');

    expect(Notification::where('user_id', $customer->id)->where('is_read', false)->count())->toBe(1);
    expect(Notification::where('user_id', $this->admin->id)->where('is_read', false)->count())->toBe(1);

    // opened from the ticket list, not from the notification itself
    $this->actingAs($customer)->get(route('customer.support.show', $ticket))->assertOk();
    expect(Notification::where('user_id', $customer->id)->where('is_read', false)->count())->toBe(0);

    $this->actingAs($this->admin)->get(route('admin.support.show', $ticket))->assertOk();
    expect(Notification::where('user_id', $this->admin->id)->where('is_read', false)->count())->toBe(0);
});

it('keeps the new-ticket form hidden until it is asked for, and has no live-chat button', function (string $role, string $route) {
    $user = supportUser($role);

    $this->actingAs($user)->get(route($route))->assertOk()
        ->assertSee('id="newTicket"', false)->assertSee('data-new-ticket', false)
        ->assertDontSee('startChatButton')->assertDontSee('المحادثة المباشرة');
})->with([
    'customer' => ['customer', 'customer.support'],
    'designer' => ['designer', 'designer.support'],
    'print shop' => ['print_provider', 'print-provider.support'],
]);

it('does not let the admin answer a solved ticket until it is reopened on purpose', function () {
    $customer = supportUser('customer');
    $ticket = \App\Support\SupportTickets::create($customer, 'مشكلة تقنية', 'عنوان الطلب', 'تفاصيل المشكلة الكاملة هنا.');
    $this->actingAs($this->admin)->postJson(route('admin.support.status', $ticket), ['status' => 'resolved'])->assertOk();

    $this->actingAs($this->admin)->postJson(route('admin.support.reply', $ticket), ['message' => 'رد متأخر'])->assertUnprocessable();
    expect($ticket->fresh()->status)->toBe(SupportTicket::STATUS_CLOSED)->and($ticket->messages()->count())->toBe(1);

    $this->actingAs($this->admin)->postJson(route('admin.support.status', $ticket), ['status' => 'open'])->assertOk()->assertJsonPath('ticket.closed', false);
    $this->actingAs($this->admin)->postJson(route('admin.support.reply', $ticket), ['message' => 'نكمل'])->assertOk();
    expect($ticket->fresh()->status)->toBe(SupportTicket::STATUS_PENDING_USER);
});
