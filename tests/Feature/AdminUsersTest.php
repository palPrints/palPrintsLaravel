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
