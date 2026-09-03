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
        ->assertSee('top-utility-bar', false);
});

test('designer sections use the shared dashboard shell', function () {
    $designer = onboardingDesigner();

    foreach ([
        'designer.designs.index',
        'designer.designs.create',
        'designer.designs.editor',
        'designer.designs.review',
        'designer.profile',
        'designer.earnings',
        'designer.settings',
        'designer.support',
        'designer.notifications',
        'designer.trends',
    ] as $routeName) {
        $this->actingAs($designer)
            ->get(route($routeName))
            ->assertOk()
            ->assertSee('profileSidebar', false)
            ->assertSee('top-utility-bar', false);
    }
});
