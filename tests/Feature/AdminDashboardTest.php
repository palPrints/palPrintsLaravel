<?php

use App\Models\ApprovalRequest;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

function adminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin dashboard renders inside the admin shell', function () {
    $this->actingAs(adminUser())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('لوحة التحكم')
        ->assertSee('إجمالي المستخدمين')
        ->assertSee('لا توجد طلبات بعد')
        ->assertSee(asset('front/css/admin/adminDashboard.css'), false);
});

test('admin dashboard shows real users and pending approvals', function () {
    $designer = User::factory()->create(['name' => 'خالد المصمم']);
    $designer->assignRole('designer');

    ApprovalRequest::create([
        'user_id' => $designer->id,
        'role' => 'designer',
        'request_number' => 'APR-1',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $this->actingAs(adminUser())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('خالد المصمم')
        ->assertSee('1 معلق')
        ->assertSee('مصممون');
});

test('only admins can open the admin dashboard', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
});
