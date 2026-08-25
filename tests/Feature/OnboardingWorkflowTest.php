<?php

use App\Models\ApprovalRequest;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Route;

function onboardingDesigner(bool $completed = false): User
{
    $user = User::factory()->create();
    $user->assignRole('designer');
    $user->designerProfile()->create([
        'full_name' => $user->name,
        'approval_status' => 'draft',
        'profile_completed_at' => $completed ? now() : null,
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
        ->assertOk();
});

test('an incomplete profile cannot submit an approval request', function () {
    $designer = onboardingDesigner();

    $this->actingAs($designer)
        ->post(route('onboarding.submit'))
        ->assertSessionHasErrors('profile');

    $this->assertDatabaseCount('approval_requests', 0);
});

test('a completed profile can submit one approval request', function () {
    $designer = onboardingDesigner(completed: true);

    $this->actingAs($designer)
        ->post(route('onboarding.submit'))
        ->assertRedirect(route('designer.dashboard'));

    $approvalRequest = ApprovalRequest::where('user_id', $designer->id)->firstOrFail();
    expect($approvalRequest->status)->toBe('submitted');
    expect($designer->fresh()->designerProfile->approval_status)->toBe('submitted');
    $this->assertDatabaseMissing('wallets', ['user_id' => $designer->id]);
    $this->assertDatabaseHas('user_notifications', [
        'user_id' => $designer->id,
        'type' => 'approval.submitted',
    ]);

    $this->actingAs($designer)
        ->post(route('onboarding.submit'))
        ->assertSessionHasErrors('profile');
    $this->assertDatabaseCount('approval_requests', 1);
});

test('an admin approval unlocks the account and creates its wallet', function () {
    $designer = onboardingDesigner(completed: true);
    $this->actingAs($designer)->post(route('onboarding.submit'));
    $approvalRequest = ApprovalRequest::where('user_id', $designer->id)->firstOrFail();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->patch(route('admin.approval-requests.update', $approvalRequest), [
            'action' => 'approved',
            'admin_notes' => 'تم التحقق من البيانات.',
        ])
        ->assertSessionHasNoErrors();

    expect($approvalRequest->fresh()->status)->toBe('approved');
    expect($designer->fresh()->designerProfile->approval_status)->toBe('approved');
    $this->assertDatabaseHas('wallets', ['user_id' => $designer->id]);
    $this->assertDatabaseHas('user_notifications', [
        'user_id' => $designer->id,
        'type' => 'approval.approved',
    ]);
});

test('approval middleware blocks sensitive features until admin approval', function () {
    Route::middleware(['web', 'auth', 'active', 'account.approved'])
        ->get('/testing/approved-business-feature', fn () => 'allowed');

    $designer = onboardingDesigner(completed: true);

    $this->actingAs($designer)
        ->get('/testing/approved-business-feature')
        ->assertRedirect(route('designer.dashboard'));

    $designer->designerProfile()->update(['approval_status' => 'approved']);

    $this->actingAs($designer->fresh())
        ->get('/testing/approved-business-feature')
        ->assertOk()
        ->assertSee('allowed');
});
