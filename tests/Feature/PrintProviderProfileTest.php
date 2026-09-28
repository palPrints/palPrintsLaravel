<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->owner = User::factory()->create(['name' => 'Shop Owner']);
    $this->owner->assignRole('print_provider');
    $this->owner->printProvider()->create(['company_name' => 'Old Name', 'approval_status' => 'draft']);
});

test('print provider profile shows stored data instead of demo values', function () {
    $this->actingAs($this->owner)
        ->get(route('print-provider.profile'))
        ->assertOk()
        ->assertSee('Old Name')
        ->assertDontSee('مطبعة الألوان الحديثة');
});

test('print provider can update the shop profile and becomes ready for review', function () {
    $this->actingAs($this->owner)
        ->patch(route('print-provider.profile.update'), [
            'company_name' => 'مطبعة الأمل',
            'contact_name' => 'Shop Owner',
            'email' => $this->owner->email,
            'phone' => '0599123456',
            'address' => 'غزة، الرمال',
            'available' => '1',
            'days' => ['الأحد', 'الإثنين'],
            'from' => '08:00',
            'to' => '18:00',
        ])
        ->assertRedirect(route('print-provider.profile'));

    $provider = $this->owner->fresh()->printProvider;

    expect($provider->company_name)->toBe('مطبعة الأمل')
        ->and($provider->address)->toBe('غزة، الرمال')
        ->and($provider->working_hours['days'])->toBe(['الأحد', 'الإثنين'])
        ->and($provider->profile_completed_at)->not->toBeNull();
});

test('print provider profile requires the essential fields', function () {
    $this->actingAs($this->owner)
        ->patch(route('print-provider.profile.update'), ['company_name' => '', 'contact_name' => 'Shop Owner', 'email' => $this->owner->email])
        ->assertSessionHasErrors(['company_name', 'phone', 'address']);
});
