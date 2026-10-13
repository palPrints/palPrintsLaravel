<?php

use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

/** The 6-digit code written in the verification mail sent to $user. */
function verificationCodeSentTo(User $user): string
{
    $code = null;

    Notification::assertSentTo($user, EmailVerificationCodeNotification::class, function ($notification) use ($user, &$code) {
        $text = implode(' ', array_map('strval', $notification->toMail($user)->introLines));
        preg_match('/\*\*(\d{6})\*\*/', $text, $found);
        $code = $found[1] ?? null;

        return true;
    });

    return (string) $code;
}

test('email verification screen asks for the code, not a link', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get('/verify-email')
        ->assertOk()
        ->assertSee('name="code"', false)
        ->assertSee(route('verification.verify'), false);
});

test('asking for verification mails a code and no link', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('verification.notice'));

    expect(verificationCodeSentTo($user))->toMatch('/^\d{6}$/');
    Notification::assertCount(1);
});

test('a second request within the minute does not send another code', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->post(route('verification.send'));
    $this->actingAs($user)->post(route('verification.send'))
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHas('status', 'verification-code-wait');

    Notification::assertCount(1);
});

test('email is verified with the mailed code', function () {
    Notification::fake();
    $this->seed(RoleAndPermissionSeeder::class);
    $user = User::factory()->unverified()->create();
    $user->assignRole('customer');
    $this->actingAs($user)->post(route('verification.send'));
    $code = verificationCodeSentTo($user);

    Event::fake([Verified::class]);

    $this->actingAs($user)
        ->post(route('verification.verify'), ['code' => $code])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('customer.profile'))
        ->assertSessionHas('status', 'email-verified');

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('email is not verified with a wrong code', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->post(route('verification.send'));

    $this->actingAs($user)
        ->from(route('verification.notice'))
        ->post(route('verification.verify'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('a code mailed to one user cannot verify another user', function () {
    Notification::fake();
    $victim = User::factory()->unverified()->create();
    $attacker = User::factory()->unverified()->create();
    $this->actingAs($victim)->post(route('verification.send'));
    $victimCode = verificationCodeSentTo($victim);
    $this->actingAs($attacker)->post(route('verification.send'));

    $this->actingAs($attacker)->post(route('verification.verify'), ['code' => $victimCode])
        ->assertSessionHasErrors('code');

    expect($victim->fresh()->hasVerifiedEmail())->toBeFalse()
        ->and($attacker->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('the code locks after too many wrong attempts and is single use', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->post(route('verification.send'));
    $code = verificationCodeSentTo($user);

    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($user)->post(route('verification.verify'), ['code' => '111111']);
    }

    $this->actingAs($user)->post(route('verification.verify'), ['code' => $code])
        ->assertSessionHasErrors('code');
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('the old verification link no longer exists', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get('/verify-email/'.$user->id.'/'.sha1($user->email))->assertNotFound();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});
