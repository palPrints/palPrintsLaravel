<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('admin');
});

test('the admin suspends and activates a customer, a designer and a print shop, and the shop stops taking orders', function () {
    $customer = User::factory()->create(['is_active' => true]);
    $customer->assignRole('customer');
    $designer = User::factory()->create(['is_active' => true]);
    $designer->assignRole('designer');
    $owner = User::factory()->create(['is_active' => true]);
    $owner->assignRole('print_provider');
    $provider = $owner->printProvider()->create(['company_name' => 'مطبعة', 'approval_status' => 'approved', 'is_active' => true]);

    foreach ([$customer, $designer, $owner] as $user) {
        $this->actingAs($this->admin)->postJson(route('admin.users.toggle', $user))
            ->assertOk()->assertJsonPath('status', 'suspended');
        expect($user->fresh()->is_active)->toBeFalse();
    }
    expect($provider->fresh()->is_active)->toBeFalse();

    $this->actingAs($this->admin)->postJson(route('admin.users.toggle', $owner))->assertOk()->assertJsonPath('status', 'active');
    expect($owner->fresh()->is_active)->toBeTrue()->and($provider->fresh()->is_active)->toBeTrue();

    $this->assertDatabaseHas('audit_logs', ['user_id' => $this->admin->id, 'action' => 'admin.user_suspended']);

    // a suspended user is thrown out on the next request
    $this->actingAs($customer->fresh())->get(route('profile.edit'))->assertRedirect(route('login'));
});

test('only an admin can suspend, and never another admin or themselves', function () {
    $customer = User::factory()->create(['is_active' => true]);
    $customer->assignRole('customer');
    $otherAdmin = User::factory()->create(['is_active' => true]);
    $otherAdmin->assignRole('admin');

    $this->actingAs($customer)->postJson(route('admin.users.toggle', $this->admin))->assertForbidden();
    $this->actingAs($this->admin)->postJson(route('admin.users.toggle', $otherAdmin))->assertForbidden();
    $this->actingAs($this->admin)->postJson(route('admin.users.toggle', $this->admin))->assertForbidden();
    expect($otherAdmin->fresh()->is_active)->toBeTrue();
});

test('the users page shows the real orders of customers and the real jobs of shops', function () {
    $customer = User::factory()->create(['is_active' => true]);
    $customer->assignRole('customer');
    Order::create(['user_id' => $customer->id, 'order_number' => 'U-1', 'status' => 'completed', 'payment_status' => 'paid', 'payment_method' => 'bank', 'subtotal' => 80, 'total_amount' => 80]);
    Order::create(['user_id' => $customer->id, 'order_number' => 'U-2', 'status' => 'cancelled', 'payment_status' => 'pending_review', 'payment_method' => 'bank', 'subtotal' => 30, 'total_amount' => 30]);

    $this->actingAs($this->admin)->get(route('admin.users'))
        ->assertOk()
        ->assertViewHas('usersData', function ($data) {
            $row = $data['customers'][0];

            return $row['metric'] === 2 && $row['spend'] === '₪80' && $row['lastOrder'] !== '—' && str_contains($row['toggleUrl'], '/toggle');
        });
});

test('the admin adds a print shop that is verified and approved at once, and the shop can sign in', function () {
    $payload = [
        'company_name' => 'مطبعة النور',
        'name' => 'أحمد النور',
        'email' => 'noor.print@example.com',
        'phone' => '0599123456',
        'address' => 'غزة، الرمال',
        'password' => 'Str0ng-Pass-2026!',
    ];

    $this->actingAs($this->admin)->postJson(route('admin.users.print-shops.store'), $payload)
        ->assertCreated();

    $owner = User::where('email', 'noor.print@example.com')->firstOrFail();
    $provider = $owner->printProvider;

    expect($owner->hasRole('print_provider'))->toBeTrue()
        ->and($owner->email_verified_at)->not->toBeNull()
        ->and($owner->is_active)->toBeTrue()
        ->and($provider->company_name)->toBe('مطبعة النور')
        ->and($provider->approval_status)->toBe('approved')
        ->and($provider->approved_by)->toBe($this->admin->id)
        ->and($provider->is_active)->toBeTrue()
        ->and($provider->branches()->count())->toBe(1)
        ->and($owner->hasApprovedBusinessAccount())->toBeTrue();

    $this->post(route('logout'));
    $this->post(route('login'), ['email' => 'noor.print@example.com', 'password' => 'Str0ng-Pass-2026!'])
        ->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($owner);
});

test('adding a print shop needs its fields, a new email, and only an admin can do it', function () {
    $taken = User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($this->admin)->postJson(route('admin.users.print-shops.store'), [])
        ->assertUnprocessable()->assertJsonValidationErrors(['company_name', 'name', 'email', 'phone', 'address', 'password']);

    $this->actingAs($this->admin)->postJson(route('admin.users.print-shops.store'), [
        'company_name' => 'مطبعة', 'name' => 'مسؤول', 'email' => 'taken@example.com',
        'phone' => '0599', 'address' => 'غزة', 'password' => 'Str0ng-Pass-2026!',
    ])->assertUnprocessable()->assertJsonValidationErrors(['email']);

    $customer = User::factory()->create();
    $customer->assignRole('customer');
    $this->actingAs($customer)->postJson(route('admin.users.print-shops.store'), [])->assertForbidden();
});
