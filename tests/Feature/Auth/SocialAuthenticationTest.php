<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function () {
    config()->set([
        'services.google.client_id' => 'google-client-id',
        'services.google.client_secret' => 'google-client-secret',
        'services.google.redirect' => 'http://localhost/auth/google/callback',
        'services.apple.client_id' => 'com.palprints.web',
        'services.apple.client_secret' => 'apple-client-secret',
        'services.apple.redirect' => 'https://palprints.example/auth/apple/callback',
    ]);
});

test('social registration redirect stores the selected role and terms intent', function () {
    Socialite::fake('google');

    $response = $this->get(route('social.redirect', [
        'provider' => 'google',
        'source' => 'register',
        'account_type' => 'designer',
        'terms' => '1',
    ]));

    $response->assertRedirect('https://socialite.fake/google/authorize');
    $response->assertSessionHas('social_auth', [
        'source' => 'register',
        'account_type' => 'designer',
    ]);
});

test('google registration creates a linked draft designer account', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-designer-1',
        'name' => 'Google Designer',
        'email' => 'designer.google@example.com',
        'verified_email' => true,
    ]));

    $response = $this
        ->withSession(['social_auth' => [
            'source' => 'register',
            'account_type' => 'designer',
        ]])
        ->get(route('social.callback', ['provider' => 'google']));

    $user = User::where('email', 'designer.google@example.com')->firstOrFail();

    $response->assertRedirect(route('designer.dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);
    expect($user->hasRole('designer'))->toBeTrue();
    expect($user->email_verified_at)->not->toBeNull();
    $this->assertDatabaseHas('social_accounts', [
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_user_id' => 'google-designer-1',
    ]);
    $this->assertDatabaseHas('designer_profiles', [
        'user_id' => $user->id,
        'approval_status' => 'draft',
    ]);
    $this->assertDatabaseMissing('wallets', ['user_id' => $user->id]);
});

test('social login links an existing user by verified email', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $user = User::factory()->unverified()->create([
        'email' => 'existing@example.com',
        'is_active' => true,
    ]);
    $user->assignRole('customer');

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-existing-1',
        'email' => 'existing@example.com',
        'verified_email' => true,
    ]));

    $response = $this
        ->withSession(['social_auth' => ['source' => 'login', 'account_type' => null]])
        ->get(route('social.callback', ['provider' => 'google']));

    $response->assertRedirect(route('customer.dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('social_accounts', [
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_user_id' => 'google-existing-1',
    ]);
    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

test('social login does not create a new account without choosing a role', function () {
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-unknown-1',
        'email' => 'unknown@example.com',
        'verified_email' => true,
    ]));

    $response = $this
        ->withSession(['social_auth' => ['source' => 'login', 'account_type' => null]])
        ->get(route('social.callback', ['provider' => 'google']));

    $response->assertRedirect(route('login', absolute: false));
    $response->assertSessionHasErrors('social');
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'unknown@example.com']);
});

test('an unverified provider email cannot be linked', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $user = User::factory()->create([
        'email' => 'unverified-provider@example.com',
        'is_active' => true,
    ]);
    $user->assignRole('customer');

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-unverified-1',
        'email' => 'unverified-provider@example.com',
        'verified_email' => false,
    ]));

    $response = $this
        ->withSession(['social_auth' => ['source' => 'login', 'account_type' => null]])
        ->get(route('social.callback', ['provider' => 'google']));

    $response->assertRedirect(route('login', absolute: false));
    $response->assertSessionHasErrors('social');
    $this->assertGuest();
    $this->assertDatabaseMissing('social_accounts', [
        'provider' => 'google',
        'provider_user_id' => 'google-unverified-1',
    ]);
});

test('a suspended account cannot sign in with a social provider', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $user = User::factory()->create([
        'email' => 'suspended@example.com',
        'is_active' => false,
    ]);
    $user->assignRole('customer');

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-suspended-1',
        'email' => 'suspended@example.com',
        'verified_email' => true,
    ]));

    $response = $this
        ->withSession(['social_auth' => ['source' => 'login', 'account_type' => null]])
        ->get(route('social.callback', ['provider' => 'google']));

    $response->assertRedirect(route('login', absolute: false));
    $response->assertSessionHasErrors('social');
    $this->assertGuest();
    $this->assertDatabaseMissing('social_accounts', [
        'provider' => 'google',
        'provider_user_id' => 'google-suspended-1',
    ]);
});

test('apple callback can create a linked draft print provider account', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    Socialite::fake('apple', SocialiteUser::fake([
        'id' => 'apple-printer-1',
        'name' => 'Apple Print Shop',
        'email' => 'printer.apple@example.com',
        'email_verified' => 'true',
    ]));

    $state = 'apple-state-without-session-cookie';
    Cache::put('social_auth_intent:'.hash('sha256', $state), [
        'source' => 'register',
        'account_type' => 'print_provider',
    ], now()->addMinutes(10));

    $response = $this->post(
        route('social.callback', ['provider' => 'apple']),
        ['state' => $state]
    );

    $user = User::where('email', 'printer.apple@example.com')->firstOrFail();

    $response->assertRedirect(route('print-provider.dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);
    expect($user->hasRole('print_provider'))->toBeTrue();
    $this->assertDatabaseHas('social_accounts', [
        'user_id' => $user->id,
        'provider' => 'apple',
        'provider_user_id' => 'apple-printer-1',
    ]);
    $this->assertDatabaseHas('print_providers', [
        'user_id' => $user->id,
        'approval_status' => 'draft',
    ]);
});
