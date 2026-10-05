<?php

use App\Models\Notification;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

function reviewTestAdmin(array $attributes = []): User
{
    $admin = User::factory()->create(array_merge(['is_active' => true], $attributes));
    $admin->assignRole('admin');

    return $admin;
}

function completeDesigner(array $profile = []): User
{
    $designer = User::factory()->create(['name' => 'Lina Designer', 'is_active' => true]);
    $designer->assignRole('designer');
    $designer->designerProfile()->create(array_merge([
        'full_name' => 'Lina Designer',
        'bio' => 'مصممة شعارات',
        'skills' => ['شعارات', 'تصوير'],
        'portfolio_url' => 'https://example.com/lina',
        'approval_status' => 'draft',
        'profile_completed_at' => now(),
    ], $profile));

    return $designer;
}

test('every active admin is notified when a designer sends an approval request', function () {
    $admin = reviewTestAdmin();
    $otherAdmin = reviewTestAdmin();
    $suspendedAdmin = reviewTestAdmin(['is_active' => false]);
    $designer = completeDesigner();

    $this->actingAs($designer)->post(route('onboarding.submit'))->assertRedirect(route('designer.dashboard'));

    foreach ([$admin, $otherAdmin] as $recipient) {
        $notification = Notification::where('user_id', $recipient->id)->where('type', 'approval.pending')->first();

        expect($notification)->not->toBeNull()
            ->and($notification->title)->toContain('Lina Designer')
            ->and($notification->title)->toContain('مصمم')
            ->and($notification->link)->toBe(route('admin.users'))
            ->and($notification->is_read)->toBeFalse();
    }

    expect(Notification::where('user_id', $suspendedAdmin->id)->count())->toBe(0)
        ->and(Notification::where('user_id', $designer->id)->where('type', 'approval.submitted')->count())->toBe(1);
});

test('no admin is notified when an incomplete profile is rejected', function () {
    $admin = reviewTestAdmin();
    $designer = completeDesigner(['portfolio_url' => null]);

    $this->actingAs($designer)->post(route('onboarding.submit'))->assertSessionHasErrors('profile');

    expect(Notification::where('user_id', $admin->id)->count())->toBe(0);
});

test('the admin bell shows the new request and links to the users page', function () {
    $admin = reviewTestAdmin();
    $designer = completeDesigner();

    $this->actingAs($designer)->post(route('onboarding.submit'));

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('طلب اعتماد جديد من مصمم: Lina Designer')
        ->assertSee('href="'.route('admin.users').'"', false);
});

test('the admin users page carries the designer review data and keeps typed html inert', function () {
    $admin = reviewTestAdmin();
    completeDesigner(['bio' => '<script>alert(1)</script> نبذة', 'skills' => ['<img src=x onerror=alert(1)>']]);

    $response = $this->actingAs($admin)->get(route('admin.users'))->assertOk();

    $response->assertSee('"jobTitle"', false)
        ->assertSee('"experience"', false)
        ->assertSee('"portfolio":"https:\/\/example.com\/lina"', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('<img src=x onerror=alert(1)>', false);
});
