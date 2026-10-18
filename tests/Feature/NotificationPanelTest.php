<?php

use App\Models\Notification;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

/** The bell menu is the same partial for every account type: it lists unread notifications, each one a button that marks it read. */
it('shows the same bell menu to every account type', function (string $role, string $home, string $readRoute) {
    $this->seed(RoleAndPermissionSeeder::class);
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole($role);

    if ($role === 'designer') {
        $user->designerProfile()->create(['full_name' => $user->name, 'approval_status' => 'approved']);
    }
    if ($role === 'print_provider') {
        $user->printProvider()->create(['company_name' => 'مطبعة', 'approval_status' => 'approved', 'is_active' => true]);
    }

    $this->actingAs($user)->get(route($home))->assertOk()->assertSee('pp-notif__empty', false)->assertSee('لا توجد إشعارات جديدة');

    $note = Notification::create(['user_id' => $user->id, 'type' => 'support.reply', 'title' => 'تم الرد على طلب الدعم', 'message' => 'نص الرد', 'link' => url('/')]);

    $this->actingAs($user)->get(route($home))
        ->assertOk()
        ->assertSee('pp-notif__item', false)
        ->assertSee('bi-headset', false)
        ->assertSee(route($readRoute, $note));

    $this->actingAs($user)->post(route($readRoute, $note))->assertRedirect();
    expect($note->fresh()->is_read)->toBeTrue();
    $this->actingAs($user)->get(route($home))->assertDontSee(route($readRoute, $note));
})->with([
    'customer' => ['customer', 'customer.store', 'customer.notifications.read'],
    'designer' => ['designer', 'designer.dashboard', 'designer.notifications.read'],
    'print shop' => ['print_provider', 'print-provider.dashboard', 'print-provider.notifications.read'],
    'admin' => ['admin', 'admin.dashboard', 'admin.notifications.read'],
]);

it('gives a chat page a new-ticket button for the user but not for the admin', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $customer = User::factory()->create(['is_active' => true]);
    $customer->assignRole('customer');
    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('admin');

    $ticket = \App\Support\SupportTickets::create($customer, 'مشكلة تقنية', 'عنوان الطلب', 'تفاصيل المشكلة الكاملة هنا.');

    $this->actingAs($customer)->get(route('customer.support.show', $ticket))->assertOk()->assertSee('pp-chat__new', false)->assertSee(route('customer.support').'#newTicket');
    $this->actingAs($customer)->get(route('customer.support'))->assertOk()->assertSee('id="newTicket"', false);
    $this->actingAs($admin)->get(route('admin.support.show', $ticket))->assertOk()->assertDontSee('pp-chat__new', false);
});
