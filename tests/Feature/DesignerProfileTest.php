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
        ->assertSee('designer-printshop-topbar', false)
        ->assertSee('Sara Designer')
        ->assertSee('Brand identity and print designer.')
        ->assertSee('Illustrator')
        ->assertSee(route('designer.profile.update'), false)
        ->assertSee(route('designer.designs.create'), false)
        ->assertSee('front/designer/css/designerProfile.css', false)
        ->assertSee('front/designer/js/designerProfile.js', false)
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
            'phone' => '0599 000-111',
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
        ->and($designer->phone)->toBe('0599000111')
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

test('phone must start with 059 or 056 and be 10 digits', function (string $phone, bool $valid) {
    $designer = designerProfileTestUser();

    $response = $this->actingAs($designer)
        ->from(route('designer.profile'))
        ->patch(route('designer.profile.update'), [
            'locale' => 'ar',
            'name' => 'مصمم',
            'email' => 'phone-check@example.com',
            'phone' => $phone,
            'bio' => 'نبذة',
            'skills' => 'تصميم',
            'portfolio_url' => 'https://example.com/me',
        ]);

    if ($valid) {
        $response->assertSessionDoesntHaveErrors('phone');
    } else {
        $response->assertSessionHasErrors('phone');
        expect($designer->fresh()->phone)->not->toBe($phone);
    }
})->with([
    '059 valid' => ['0591234567', true],
    '056 valid' => ['0561234567', true],
    'spaces and dash are cleaned' => ['059 123-4567', true],
    '052 prefix' => ['0521234567', false],
    '9 digits' => ['059123456', false],
    '11 digits' => ['05912345678', false],
    'country code' => ['+970591234567', false],
    'letters' => ['059abc4567', false],
]);

test('phone is optional', function () {
    $designer = designerProfileTestUser();

    $this->actingAs($designer)
        ->patch(route('designer.profile.update'), [
            'locale' => 'ar',
            'name' => 'مصمم',
            'email' => 'nophone@example.com',
            'phone' => '',
            'bio' => 'نبذة',
            'skills' => 'تصميم',
            'portfolio_url' => 'https://example.com/me',
        ])
        ->assertSessionDoesntHaveErrors('phone');
});

test('complete designer profile can submit an approval request', function () {
    $designer = designerProfileTestUser();

    $this->actingAs($designer)
        ->patch(route('designer.profile.update'), [
            'locale' => 'ar',
            'name' => 'مصمم مكتمل',
            'email' => 'complete@example.com',
            'bio' => 'نبذة مهنية للمصمم.',
            'skills' => 'تصميم، هوية بصرية',
            'portfolio_url' => 'https://example.com/portfolio',
        ])
        ->assertRedirect(route('designer.profile'));

    $designer->refresh();
    expect($designer->designerProfile->profile_completed_at)->not->toBeNull();

    $this->actingAs($designer)
        ->post(route('onboarding.submit'))
        ->assertRedirect(route('designer.dashboard'));

    expect($designer->fresh()->designerProfile->approval_status)->toBe('submitted')
        ->and($designer->approvalRequests()->count())->toBe(1);
});

test('a profile missing the portfolio link is not marked complete and cannot be submitted', function () {
    $designer = designerProfileTestUser();

    $this->actingAs($designer)
        ->patch(route('designer.profile.update'), [
            'locale' => 'ar',
            'name' => 'مصمم ناقص',
            'email' => 'partial@example.com',
            'bio' => 'نبذة مهنية للمصمم.',
            'skills' => 'تصميم، هوية بصرية',
        ])
        ->assertRedirect(route('designer.profile'));

    expect($designer->fresh()->designerProfile->profile_completed_at)->toBeNull();

    $this->actingAs($designer)
        ->post(route('onboarding.submit'))
        ->assertSessionHasErrors('profile');

    expect($designer->fresh()->designerProfile->approval_status)->toBe('draft')
        ->and($designer->approvalRequests()->count())->toBe(0);
});

test('a stale completed flag cannot be used to submit a profile whose saved data is incomplete', function () {
    $designer = designerProfileTestUser([], [
        'bio' => 'نبذة',
        'skills' => ['تصميم'],
        'portfolio_url' => null,
        'profile_completed_at' => now(),
    ]);

    $this->actingAs($designer)
        ->post(route('onboarding.submit'))
        ->assertSessionHasErrors('profile');

    expect(session('errors')->first('profile'))->toContain('رابط معرض الأعمال')
        ->and($designer->fresh()->designerProfile->approval_status)->toBe('draft')
        ->and($designer->approvalRequests()->count())->toBe(0);
});

test('the send for approval button only shows once the saved profile is complete', function () {
    $incomplete = designerProfileTestUser([], ['bio' => 'نبذة', 'skills' => ['تصميم'], 'profile_completed_at' => now()]);

    $this->actingAs($incomplete)
        ->get(route('designer.profile'))
        ->assertOk()
        ->assertDontSee('إرسال للاعتماد');

    $complete = designerProfileTestUser([], [
        'bio' => 'نبذة',
        'skills' => ['تصميم'],
        'portfolio_url' => 'https://example.com/me',
        'profile_completed_at' => now(),
    ]);

    $this->actingAs($complete)
        ->get(route('designer.profile'))
        ->assertOk()
        ->assertSee('إرسال للاعتماد');
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
