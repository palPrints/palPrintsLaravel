<?php

use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('designer withdrawal request is stored and moves wallet balance to pending', function () {
    $designer = User::factory()->create();
    $designer->assignRole('designer');
    $designer->designerProfile()->create([
        'full_name' => $designer->name,
        'approval_status' => 'approved',
    ]);

    $wallet = Wallet::create([
        'user_id' => $designer->id,
        'total_balance' => 480,
        'available_balance' => 245,
        'pending_balance' => 180,
        'total_withdrawn' => 55,
    ]);

    $this->actingAs($designer)
        ->postJson(route('designer.earnings.withdraw'), [
            'amount' => 100,
            'method' => 'wallet',
            'notes' => 'Demo withdrawal request',
        ])
        ->assertCreated()
        ->assertJsonStructure(['id', 'message']);

    $this->assertDatabaseHas('withdrawal_requests', [
        'user_id' => $designer->id,
        'wallet_id' => $wallet->id,
        'amount' => 100,
        'method' => 'wallet',
        'status' => 'pending',
    ]);

    expect($wallet->fresh()->available_balance)->toEqual('145.00')
        ->and($wallet->fresh()->pending_balance)->toEqual('280.00')
        ->and(WithdrawalRequest::where('user_id', $designer->id)->count())->toBe(1);
});
