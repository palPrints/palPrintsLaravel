<?php

use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->provider = User::factory()->create();
    $this->provider->assignRole('print_provider');
    $this->provider->printProvider()->create(['company_name' => 'مطبعة', 'approval_status' => 'approved', 'is_active' => true]);
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
            'holder' => 'مطبعة النور', 'account' => '0599123456',
        ])
        ->assertCreated();

    expect($this->wallet->fresh()->available_balance)->toEqual('300.00')
        ->and($this->wallet->fresh()->pending_balance)->toEqual('300.00');

    $this->assertDatabaseHas('withdrawal_requests', ['user_id' => $this->provider->id, 'method' => 'palpay', 'status' => 'pending']);
});

test('print provider withdrawal rejects amounts below the minimum or above the balance', function () {
    $this->actingAs($this->provider)
        ->postJson(route('print-provider.earnings.withdraw'), ['amount' => 50, 'method' => 'palpay', 'holder' => 'مطبعة النور', 'account' => '0599123456'])
        ->assertUnprocessable()->assertJsonValidationErrors('amount');

    $this->actingAs($this->provider)
        ->postJson(route('print-provider.earnings.withdraw'), ['amount' => 900, 'method' => 'palpay', 'holder' => 'مطبعة النور', 'account' => '0599123456'])
        ->assertUnprocessable()->assertJsonValidationErrors('amount');

    expect($this->wallet->fresh()->available_balance)->toEqual('500.00');
});

test('a withdrawal needs the account holder and a phone number, or an IBAN for the bank, and the admin sees them', function () {
    $post = fn (array $data) => $this->actingAs($this->provider)
        ->postJson(route('print-provider.earnings.withdraw'), $data + ['amount' => 100]);

    $post(['method' => 'palpay', 'account' => '0599123456'])->assertUnprocessable()->assertJsonValidationErrors('holder');
    $post(['method' => 'palpay', 'holder' => 'مطبعة النور', 'account' => 'PS92PALS000000000400123456702'])->assertUnprocessable()->assertJsonValidationErrors('account');
    $post(['method' => 'jawwal-pay', 'holder' => 'مطبعة النور', 'account' => '12'])->assertUnprocessable()->assertJsonValidationErrors('account');
    $post(['method' => 'bank-of-palestine', 'holder' => 'مطبعة النور', 'account' => 'عشوائي'])->assertUnprocessable()->assertJsonValidationErrors('account');
    expect($this->wallet->fresh()->available_balance)->toEqual('500.00');

    // the bank takes a phone number or an IBAN (spaces and small letters are fine)
    $post(['method' => 'bank-of-palestine', 'holder' => 'مطبعة النور', 'account' => '0599123456'])->assertCreated();
    $post(['method' => 'bank-of-palestine', 'holder' => 'مطبعة النور', 'account' => 'ps92 pals 0000 0000 0400 1234 5670 2'])->assertCreated();

    $this->assertDatabaseHas('withdrawal_requests', ['user_id' => $this->provider->id, 'method' => 'bank-of-palestine']);
    expect(\App\Models\WithdrawalRequest::latest('id')->first()->account_details)
        ->toBe(['account' => 'PS92PALS000000000400123456702', 'holder' => 'مطبعة النور']);

    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('admin');
    $this->actingAs($admin)->get(route('admin.payments'))
        ->assertOk()
        ->assertSee('data-payout-holder="مطبعة النور"', false)
        ->assertSee('data-payout-account="PS92PALS000000000400123456702"', false);
});
