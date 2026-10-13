<?php

use App\Models\User;
use App\Notifications\PasswordChangeCodeNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

/** Asks for a code the way the settings page does and returns the code that was mailed. */
function requestPasswordCode(User $user): string
{
    Notification::fake();
    test()->actingAs($user)->postJson('/password/code')->assertOk();

    $code = null;
    Notification::assertSentTo($user, PasswordChangeCodeNotification::class, function ($notification) use (&$code) {
        $code = (fn () => $this->code)->call($notification);

        return true;
    });

    return $code;
}

test('password can be updated with the emailed code', function () {
    $user = User::factory()->create();
    $code = requestPasswordCode($user);

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
            'code' => $code,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
});

test('password cannot be updated without a valid code', function () {
    $user = User::factory()->create();
    requestPasswordCode($user);

    foreach (['', '000000'] as $wrong) {
        $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
            'code' => $wrong,
        ])->assertSessionHasErrorsIn('updatePassword', 'code');
    }

    $this->assertTrue(Hash::check('password', $user->refresh()->password));
});

test('a code is locked after too many wrong attempts and is single use', function () {
    $user = User::factory()->create();
    $code = requestPasswordCode($user);
    $payload = ['current_password' => 'password', 'password' => 'new-password', 'password_confirmation' => 'new-password'];

    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($user)->put('/password', $payload + ['code' => '111111']);
    }

    $this->actingAs($user)->put('/password', $payload + ['code' => $code])
        ->assertSessionHasErrorsIn('updatePassword', 'code');
    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

test('a code cannot be requested again within a minute', function () {
    $user = User::factory()->create();
    Cache::flush();
    requestPasswordCode($user);

    $this->actingAs($user)->postJson('/password/code')->assertStatus(429);
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();
    $code = requestPasswordCode($user);

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
            'code' => $code,
        ]);

    $response
        ->assertSessionHasErrorsIn('updatePassword', 'current_password')
        ->assertRedirect('/profile');
});
