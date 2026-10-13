<?php

use App\Models\Notification;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use Database\Seeders\RoleAndPermissionSeeder;

function approvedDesigner(): User
{
    $user = User::factory()->create();
    $user->assignRole('designer');
    $user->designerProfile()->create([
        'full_name' => $user->name,
        'approval_status' => 'approved',
    ]);

    return $user;
}

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('every designer page renders inside the new shared shell', function (string $routeName) {
    $designer = approvedDesigner();

    $this->actingAs($designer)
        ->get(route($routeName))
        ->assertOk()
        ->assertSee('designer-printshop-topbar', false)
        ->assertSee('profileSidebar', false)
        ->assertSee(route('logout'), false);
})->with([
    'designer.dashboard',
    'designer.designs.index',
    'designer.designs.create',
    'designer.earnings',
    'designer.profile',
    'designer.settings',
    'designer.notifications',
    'designer.support',
    'designer.trends',
]);

test('withdrawal above the available balance is rejected as json validation error', function () {
    $designer = approvedDesigner();
    $wallet = Wallet::create([
        'user_id' => $designer->id,
        'total_balance' => 100,
        'available_balance' => 50,
        'pending_balance' => 0,
        'total_withdrawn' => 0,
    ]);

    $this->actingAs($designer)
        ->postJson(route('designer.earnings.withdraw'), ['amount' => 80, 'method' => 'bank'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('amount');

    expect($wallet->fresh()->available_balance)->toEqual('50.00')
        ->and(WithdrawalRequest::count())->toBe(0);
});

test('withdrawal records a wallet transaction and pending balance', function () {
    $designer = approvedDesigner();
    $wallet = Wallet::create([
        'user_id' => $designer->id,
        'total_balance' => 200,
        'available_balance' => 150,
        'pending_balance' => 10,
        'total_withdrawn' => 0,
    ]);

    // The platform's minimum withdrawal is ₪100, so the request has to be at least that.
    $this->actingAs($designer)
        ->postJson(route('designer.earnings.withdraw'), ['amount' => 120.5, 'method' => 'wallet'])
        ->assertCreated();

    $transaction = WalletTransaction::where('wallet_id', $wallet->id)->firstOrFail();

    expect($wallet->fresh()->available_balance)->toEqual('29.50')
        ->and($wallet->fresh()->pending_balance)->toEqual('130.50')
        ->and($transaction->type)->toBe('withdrawal')
        ->and($transaction->status)->toBe('requested');
});

test('designer can read and mark only their own notifications', function () {
    $designer = approvedDesigner();
    $other = approvedDesigner();

    $mine = Notification::create(['user_id' => $designer->id, 'type' => 'design_published', 'title' => 'Mine', 'message' => 'x']);
    $theirs = Notification::create(['user_id' => $other->id, 'type' => 'design_published', 'title' => 'Theirs', 'message' => 'x']);

    $this->actingAs($designer)
        ->get(route('designer.notifications'))
        ->assertOk()
        ->assertSee('Mine')
        ->assertDontSee('Theirs');

    $this->actingAs($designer)
        ->post(route('designer.notifications.read', $theirs))
        ->assertNotFound();

    $this->actingAs($designer)
        ->post(route('designer.notifications.read', $mine))
        ->assertRedirect(route('designer.notifications'));

    expect($mine->fresh()->is_read)->toBeTrue()
        ->and($theirs->fresh()->is_read)->toBeFalse();
});

test('designer can update account settings', function () {
    $designer = approvedDesigner();

    $this->actingAs($designer)
        ->patch(route('designer.settings.account'), [
            'name' => 'New Name',
            'email' => 'NEW-EMAIL@example.com',
            'phone' => '0599000000',
        ])
        ->assertRedirect(route('designer.settings'))
        ->assertSessionHas('settings_status', 'account-updated');

    $designer->refresh();

    expect($designer->name)->toBe('New Name')
        ->and($designer->email)->toBe('new-email@example.com')
        ->and($designer->email_verified_at)->toBeNull()
        ->and($designer->designerProfile->full_name)->toBe('New Name');
});

test('account settings reject an email used by someone else', function () {
    $designer = approvedDesigner();
    $other = User::factory()->create();

    $this->actingAs($designer)
        ->from(route('designer.settings'))
        ->patch(route('designer.settings.account'), [
            'name' => 'Valid Name',
            'email' => $other->email,
        ])
        ->assertRedirect(route('designer.settings'))
        ->assertSessionHasErrors('email', errorBag: 'updateAccount');
});
