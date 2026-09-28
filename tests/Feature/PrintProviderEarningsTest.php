<?php

use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->provider = User::factory()->create();
    $this->provider->assignRole('print_provider');
    $this->wallet = Wallet::create([
        'user_id' => $this->provider->id,
        'total_balance' => 1000,
        'available_balance' => 500,
        'pending_balance' => 100,
        'total_withdrawn' => 0,
    ]);
});

test('print provider earnings page shows the real wallet balances', function () {
    $this->actingAs($this->provider)
        ->get(route('print-provider.earnings'))
        ->assertOk()
        ->assertSee('500.00 ₪')
        ->assertSee('1,000.00 ₪')
        ->assertDontSee('2,430.00 ₪');
});

test('print provider can request a withdrawal within the available balance', function () {
    $this->actingAs($this->provider)
        ->postJson(route('print-provider.earnings.withdraw'), [
            'amount' => 200,
            'method' => 'palpay',
            'account' => '0599123456',
        ])
        ->assertCreated();

    expect($this->wallet->fresh()->available_balance)->toEqual('300.00')
        ->and($this->wallet->fresh()->pending_balance)->toEqual('300.00');

    $this->assertDatabaseHas('withdrawal_requests', ['user_id' => $this->provider->id, 'method' => 'palpay', 'status' => 'pending']);
});

test('print provider withdrawal rejects amounts below the minimum or above the balance', function () {
    $this->actingAs($this->provider)
        ->postJson(route('print-provider.earnings.withdraw'), ['amount' => 50, 'method' => 'palpay', 'account' => '0599123456'])
        ->assertUnprocessable()->assertJsonValidationErrors('amount');

    $this->actingAs($this->provider)
        ->postJson(route('print-provider.earnings.withdraw'), ['amount' => 900, 'method' => 'palpay', 'account' => '0599123456'])
        ->assertUnprocessable()->assertJsonValidationErrors('amount');

    expect($this->wallet->fresh()->available_balance)->toEqual('500.00');
});
