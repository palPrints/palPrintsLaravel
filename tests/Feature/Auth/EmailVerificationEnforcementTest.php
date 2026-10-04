<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function () {
    // Email verification is switched off for now; remove this line when it is turned back on.
    $this->markTestSkipped('Email verification enforcement is disabled for now.');

    $this->seed(RoleAndPermissionSeeder::class);
});

function unverifiedUserWithRole(string $role): User
{
    $user = User::factory()->unverified()->create(['is_active' => true]);
    $user->assignRole($role);

    return $user;
}

test('registering sends a verification email and does not log the user in', function () {
    Notification::fake();

    $this->post('/register', [
        'name' => 'Fresh User',
        'email' => 'fresh@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'account_type' => 'customer',
        'terms' => '1',
    ])->assertRedirect(route('login', absolute: false));

    $user = User::where('email', 'fresh@example.com')->firstOrFail();

    $this->assertGuest();
    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('unverified accounts of every role are sent to the verification page', function (string $role, string $route) {
    $user = unverifiedUserWithRole($role);

    $this->actingAs($user)
        ->get(route($route))
        ->assertRedirect(route('verification.notice'));
})->with([
    'customer dashboard' => ['customer', 'dashboard'],
    'customer store' => ['customer', 'customer.store'],
    'designer dashboard' => ['designer', 'designer.dashboard'],
    'print provider dashboard' => ['print_provider', 'print-provider.dashboard'],
    'admin dashboard' => ['admin', 'admin.dashboard'],
    'account profile' => ['customer', 'profile.edit'],
]);

test('unverified users can still open the verification page and log out', function () {
    $user = unverifiedUserWithRole('customer');

    $this->actingAs($user)->get(route('verification.notice'))->assertOk();

    $this->actingAs($user)->post(route('logout'))->assertRedirect('/');
    $this->assertGuest();
});

test('unverified users can ask for a new verification email', function () {
    Notification::fake();
    $user = unverifiedUserWithRole('customer');

    $this->actingAs($user)->post(route('verification.send'));

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('verified users are not stopped by the verification gate', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('customer');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('customer.store', absolute: false));
});

test('full flow: register, get blocked, click the email link, then get in', function () {
    $this->post('/register', [
        'name' => 'Flow User',
        'email' => 'flow@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'account_type' => 'customer',
        'terms' => '1',
    ]);

    $this->post('/login', ['email' => 'flow@example.com', 'password' => 'password']);
    $this->assertAuthenticated();

    $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));

    $user = User::where('email', 'flow@example.com')->firstOrFail();
    $link = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->get($link);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

    $this->get(route('dashboard'))->assertRedirect(route('customer.store', absolute: false));
});

test('a tampered verification link does not verify the account', function () {
    $user = unverifiedUserWithRole('customer');

    $link = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1('someone-else@example.com'),
    ]);

    $this->actingAs($user)->get($link)->assertForbidden();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('an expired verification link does not verify the account', function () {
    $user = unverifiedUserWithRole('customer');

    $link = URL::temporarySignedRoute('verification.verify', now()->subMinute(), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->actingAs($user)->get($link)->assertForbidden();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('one user cannot verify another users email', function () {
    $victim = unverifiedUserWithRole('customer');
    $attacker = unverifiedUserWithRole('customer');

    $link = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $victim->id,
        'hash' => sha1($victim->email),
    ]);

    $this->actingAs($attacker)->get($link)->assertForbidden();
    expect($victim->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('a google registered account lands inside the app without a verification step', function () {
    config()->set([
        'services.google.client_id' => 'google-client-id',
        'services.google.client_secret' => 'google-client-secret',
        'services.google.redirect' => 'http://localhost/auth/google/callback',
    ]);
    Notification::fake();

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-flow-1',
        'name' => 'Google Customer',
        'email' => 'google.customer@example.com',
        'verified_email' => true,
    ]));

    $this->withSession(['social_auth' => ['source' => 'register', 'account_type' => 'customer']])
        ->get(route('social.callback', ['provider' => 'google']))
        ->assertRedirect(route('customer.store', absolute: false));

    $user = User::where('email', 'google.customer@example.com')->firstOrFail();

    expect($user->hasVerifiedEmail())->toBeTrue();
    Notification::assertNothingSent();

    $this->get(route('dashboard'))->assertRedirect(route('customer.store', absolute: false));
});
