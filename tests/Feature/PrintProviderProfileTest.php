<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\ApprovalRequest;
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

test('saving a complete print provider profile sends the approval request to the admins', function () {
    Storage::fake('public');
    Storage::fake('local');

    $this->actingAs($this->owner)
        ->patch(route('print-provider.profile.update'), [
            'company_name' => 'مطبعة الأمل',
            'contact_name' => 'Shop Owner',
            'email' => $this->owner->email,
            'phone' => '0599123456',
            'owner_phone' => '0599000111',
            'whatsapp_number' => '0599123456',
            'verification_document' => UploadedFile::fake()->create('license.pdf', 50, 'application/pdf'),
            'id_document' => UploadedFile::fake()->create('id.pdf', 50, 'application/pdf'),
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
        ->and($provider->profile_completed_at)->not->toBeNull()
        ->and($provider->approval_status)->toBe('submitted')
        ->and(ApprovalRequest::where('user_id', $this->owner->id)->where('status', 'submitted')->count())->toBe(1);
});

test('saving a print provider profile without the documents does not send it for approval', function () {
    $this->actingAs($this->owner)
        ->patch(route('print-provider.profile.update'), [
            'company_name' => 'مطبعة الأمل',
            'contact_name' => 'Shop Owner',
            'email' => $this->owner->email,
            'phone' => '0599123456',
            'owner_phone' => '0599000111',
            'whatsapp_number' => '0599123456',
            'address' => 'غزة، الرمال',
        ])
        ->assertRedirect(route('print-provider.profile'))
        ->assertSessionHas('profile_status', 'updated');

    expect($this->owner->fresh()->printProvider->approval_status)->toBe('draft')
        ->and(ApprovalRequest::where('user_id', $this->owner->id)->exists())->toBeFalse();
});

test('the whatsapp number of the shop is required', function () {
    $this->actingAs($this->owner)
        ->patch(route('print-provider.profile.update'), [
            'company_name' => 'مطبعة الأمل',
            'contact_name' => 'Shop Owner',
            'email' => $this->owner->email,
            'phone' => '0599123456',
            'owner_phone' => '0599000111',
            'address' => 'غزة، الرمال',
        ])
        ->assertSessionHasErrors(['whatsapp_number']);
});

test('print provider profile requires the essential fields', function () {
    $this->actingAs($this->owner)
        ->patch(route('print-provider.profile.update'), ['company_name' => '', 'contact_name' => 'Shop Owner', 'email' => $this->owner->email])
        ->assertSessionHasErrors(['company_name', 'phone', 'address']);
});
