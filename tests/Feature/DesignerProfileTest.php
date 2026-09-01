<?php

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function designerProfileTestUser(array $userAttributes = [], array $profileAttributes = []): User
{
    $user = User::factory()->create($userAttributes);
    $user->assignRole('designer');
    $user->designerProfile()->create(array_merge([
        'full_name' => $user->name,
        'approval_status' => 'draft',
    ], $profileAttributes));

    return $user;
}

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('designer profile renders real data inside the shared designer shell', function () {
    $designer = designerProfileTestUser([
        'name' => 'Sara Designer',
        'email' => 'sara@example.com',
        'phone' => '+970599123456',
    ], [
        'bio' => 'Brand identity and print designer.',
        'skills' => ['Illustrator', 'Branding'],
        'portfolio_url' => 'https://behance.net/sara',
        'profile_image' => 'designer/profile-images/missing.png',
        'approval_status' => 'approved',
    ]);

    $this->actingAs($designer)
        ->get(route('designer.profile'))
        ->assertOk()
        ->assertViewIs('designer.profile')
        ->assertSee('profileSidebar', false)
        ->assertSee('top-utility-bar', false)
        ->assertSee('Sara Designer')
        ->assertSee('Brand identity and print designer.')
        ->assertSee('Illustrator')
        ->assertSee(route('designer.profile.update'), false)
        ->assertSee(route('designer.designs.create'), false)
        ->assertSee('front/designer/css/profile.css', false)
        ->assertSee('front/designer/js/profile.js', false)
        ->assertDontSee('designer/profile-images/missing.png', false);
});

test('designer can update profile data and upload a profile image', function () {
    Storage::fake('public');

    $designer = designerProfileTestUser([
        'email' => 'old-designer@example.com',
        'email_verified_at' => now(),
    ]);
    $oldImagePath = 'designer/profile-images/old-avatar.png';
    Storage::disk('public')->put($oldImagePath, 'old image');
    $designer->designerProfile()->update(['profile_image' => $oldImagePath]);
    $image = UploadedFile::fake()->createWithContent(
        'avatar.png',
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl2nWQAAAAASUVORK5CYII=')
    );

    $this->actingAs($designer)
        ->patch(route('designer.profile.update'), [
            'locale' => 'en',
            'name' => 'Updated Designer',
            'email' => 'UPDATED-DESIGNER@EXAMPLE.COM',
            'phone' => '+970599000111',
            'portfolio_url' => 'https://behance.net/updated',
            'skills' => 'Branding, Illustrator, Branding',
            'bio' => 'Updated professional bio.',
            'profile_image' => $image,
        ])
        ->assertRedirect(route('designer.profile'))
        ->assertSessionHas('profile_status', 'updated');

    $designer->refresh();
    $profile = $designer->designerProfile()->firstOrFail();

    expect($designer->name)->toBe('Updated Designer')
        ->and($designer->email)->toBe('updated-designer@example.com')
        ->and($designer->phone)->toBe('+970599000111')
        ->and($designer->email_verified_at)->toBeNull()
        ->and($profile->full_name)->toBe('Updated Designer')
        ->and($profile->portfolio_url)->toBe('https://behance.net/updated')
        ->and($profile->skills)->toBe(['Branding', 'Illustrator'])
        ->and($profile->bio)->toBe('Updated professional bio.')
        ->and($profile->profile_image)->not->toBeNull();

    Storage::disk('public')->assertExists($profile->profile_image);
    Storage::disk('public')->assertMissing($oldImagePath);

    expect(AuditLog::query()
        ->where('user_id', $designer->id)
        ->where('action', 'designer.profile_updated')
        ->exists())->toBeTrue();
});

test('designer profile update returns localized validation errors', function () {
    $designer = designerProfileTestUser();
    $otherUser = User::factory()->create(['email' => 'used@example.com']);

    $response = $this->actingAs($designer)
        ->from(route('designer.profile'))
        ->patch(route('designer.profile.update'), [
            'locale' => 'en',
            'name' => '',
            'email' => $otherUser->email,
            'portfolio_url' => 'not-a-url',
        ]);

    $response
        ->assertRedirect(route('designer.profile'))
        ->assertSessionHasErrors(['name', 'email', 'portfolio_url']);

    expect(session('errors')->first('name'))->toBe('Enter your full name.')
        ->and(session('errors')->first('email'))->toBe('This email address is already in use.')
        ->and(session('errors')->first('portfolio_url'))->toBe('Enter a valid portfolio link starting with http or https.');
});

test('non designer accounts cannot access designer profile routes', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $this->actingAs($customer)
        ->get(route('designer.profile'))
        ->assertForbidden();

    $this->actingAs($customer)
        ->patch(route('designer.profile.update'), [
            'name' => 'Customer Name',
            'email' => $customer->email,
        ])
        ->assertForbidden();
});
