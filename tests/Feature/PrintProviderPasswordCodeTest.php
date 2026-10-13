<?php

use App\Models\User;
use App\Notifications\PasswordChangeCodeNotification;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->provider = User::factory()->create(['email' => 'shop@example.com']);
    $this->provider->assignRole('print_provider');
});

/** The 6-digit code written in the mail that was sent to $user. */
function mailedCode(User $user): string
{
    $code = null;

    Notification::assertSentTo($user, PasswordChangeCodeNotification::class, function ($notification) use ($user, &$code) {
        $mail = $notification->toMail($user);
        $text = $mail->subject.' '.implode(' ', array_map('strval', $mail->introLines));
        preg_match('/\*\*(\d{6})\*\*/', $text, $found);
        $code = $found[1] ?? null;

        return true;
    });

    return (string) $code;
}

test('the print provider settings page offers the email code step', function () {
    $this->actingAs($this->provider)
        ->get(route('print-provider.settings'))
        ->assertOk()
        ->assertSee('إرسال الرمز')
        ->assertSee('name="code"', false)
        ->assertSee(route('password.code'), false);
});

test('a verification code is mailed to the signed-in print provider only', function () {
    Notification::fake();
    $other = User::factory()->create();

    $this->actingAs($this->provider)
        ->postJson(route('password.code'))
        ->assertOk()
        ->assertJsonPath('retry_after', 60)
        ->assertJsonFragment(['message' => 'أرسلنا رمز التحقق إلى sh**@example.com. الرمز صالح لمدة 10 دقائق.']);

    Notification::assertSentTo($this->provider, PasswordChangeCodeNotification::class);
    Notification::assertNotSentTo($other, PasswordChangeCodeNotification::class);
    Notification::assertCount(1);

    expect(mailedCode($this->provider))->toMatch('/^\d{6}$/');
});

test('a guest cannot request a code', function () {
    Notification::fake();

    $this->postJson(route('password.code'))->assertUnauthorized();

    Notification::assertNothingSent();
});

test('the print provider changes the password with the mailed code', function () {
    Notification::fake();
    $this->actingAs($this->provider)->postJson(route('password.code'))->assertOk();
    $code = mailedCode($this->provider);

    $this->actingAs($this->provider)
        ->from(route('print-provider.settings'))
        ->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
            'code' => $code,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('print-provider.settings'));

    expect(Hash::check('new-password-1', $this->provider->fresh()->password))->toBeTrue();

    // The code is single use: the same code cannot change the password again.
    $this->actingAs($this->provider)
        ->put(route('password.update'), [
            'current_password' => 'new-password-1',
            'password' => 'another-password-2',
            'password_confirmation' => 'another-password-2',
            'code' => $code,
        ])
        ->assertSessionHasErrorsIn('updatePassword', 'code');
});

test('the password is not changed with a wrong code, and a code sent to someone else does not work', function () {
    Notification::fake();
    $other = User::factory()->create();
    $this->actingAs($other)->postJson(route('password.code'))->assertOk();
    $othersCode = mailedCode($other);

    $this->actingAs($this->provider)->postJson(route('password.code'))->assertOk();

    foreach (['000000', $othersCode] as $wrong) {
        $this->actingAs($this->provider)
            ->put(route('password.update'), [
                'current_password' => 'password',
                'password' => 'new-password-1',
                'password_confirmation' => 'new-password-1',
                'code' => $wrong,
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'code');
    }

    expect(Hash::check('password', $this->provider->fresh()->password))->toBeTrue();
});
