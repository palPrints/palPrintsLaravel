<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

function onboardingDesigner(): User
{
    $user = User::factory()->create();
    $user->assignRole('designer');
    $user->designerProfile()->create([
        'full_name' => $user->name,
        'approval_status' => 'draft',
    ]);

    return $user;
}

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('a business account can enter its dashboard before approval', function () {
    $designer = onboardingDesigner();

    $this->actingAs($designer)
        ->get('/designer/dashboard')
        ->assertOk()
        ->assertViewIs('designer.dashboard')
        ->assertSee('profileSidebar', false)
        ->assertSee('designer-printshop-topbar', false);
});

test('unapproved designer cannot access design creation pages', function () {
    $designer = onboardingDesigner();

    foreach ([
        'designer.designs.index',
        'designer.designs.create',
        'designer.designs.review',
    ] as $routeName) {
        $this->actingAs($designer)
            ->get(route($routeName))
            ->assertRedirect(route('designer.dashboard'))
            ->assertSessionHas('warning');
    }
});

test('approved designer can access design creation pages', function () {
    $designer = onboardingDesigner();
    $designer->designerProfile->update(['approval_status' => 'approved']);

    $this->actingAs($designer)
        ->get(route('designer.designs.create'))
        ->assertOk()
        ->assertViewIs('designer.designs.create');
});

    test('unapproved designer cannot access earnings', function () {
        $designer = onboardingDesigner();

        $this->actingAs($designer)
        ->get(route('designer.earnings'))
        ->assertRedirect(route('designer.dashboard'))
        ->assertSessionHas('warning', 'أكمل ملفك الشخصي أولًا، ثم أرسل الحساب للتوثيق حتى تتمكن من استخدام هذه الصفحة.');
    });

test('designer sections use the shared dashboard shell', function () {
    $designer = onboardingDesigner();

    foreach ([
        'designer.profile',
        'designer.settings',
        'designer.support',
        'designer.notifications',
        'designer.trends',
    ] as $routeName) {
        $this->actingAs($designer)
            ->get(route($routeName))
            ->assertOk()
            ->assertSee('profileSidebar', false)
            ->assertSee('designer-printshop-topbar', false);
    }
});
