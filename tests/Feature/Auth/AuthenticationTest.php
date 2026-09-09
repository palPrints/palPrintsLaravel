<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('login validation errors are available in Arabic and English', function () {
    $response = $this->from('/login')->post('/login', [
        'locale' => 'ar',
        'email' => 'not-an-email',
        'password' => '',
    ]);

    $response
        ->assertRedirect('/login')
        ->assertSessionHasErrors([
            'email' => 'أدخل بريدًا إلكترونيًا صحيحًا، مثل name@example.com.',
            'password' => 'أدخل كلمة المرور.',
        ])
        ->assertSessionHas('login_error_translations', [
            'email' => [
                'ar' => 'أدخل بريدًا إلكترونيًا صحيحًا، مثل name@example.com.',
                'en' => 'Enter a valid email address, such as name@example.com.',
            ],
            'password' => [
                'ar' => 'أدخل كلمة المرور.',
                'en' => 'Enter your password.',
            ],
        ]);
});

test('invalid credentials use the selected English login language', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'locale' => 'en',
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response
        ->assertSessionHasErrors([
            'email' => 'The login details are incorrect. Check your email address and password.',
        ])
        ->assertSessionHas('login_error_translations.email', [
            'ar' => 'بيانات تسجيل الدخول غير صحيحة. تحقّق من البريد الإلكتروني وكلمة المرور.',
            'en' => 'The login details are incorrect. Check your email address and password.',
        ]);
});

test('too many login attempts have bilingual messages', function () {
    $email = 'rate-limited-login@example.com';
    $throttleKey = Str::transliterate(Str::lower($email).'|127.0.0.1');
    RateLimiter::clear($throttleKey);

    foreach (range(1, 5) as $_) {
        $this->post('/login', [
            'locale' => 'en',
            'email' => $email,
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->post('/login', [
        'locale' => 'en',
        'email' => $email,
        'password' => 'wrong-password',
    ]);

    $englishMessage = session('errors')->first('email');
    $translations = session('login_error_translations.email');

    expect($englishMessage)->toStartWith('Too many login attempts. Try again in ');
    expect($translations['ar'])->toStartWith('تم تجاوز عدد محاولات تسجيل الدخول. حاول مجددًا بعد ');
    expect($translations['en'])->toBe($englishMessage);
    $response->assertSessionHasErrors('email');

    RateLimiter::clear($throttleKey);
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('customer is redirected to the customer store', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('customer');

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('customer.store', absolute: false));
    expect($user->fresh()->last_login_at)->not->toBeNull();
    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $user->id,
        'action' => 'account.logged_in',
    ]);
});

test('draft designer is redirected to the designer dashboard', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('designer');
    $user->designerProfile()->create([
        'full_name' => $user->name,
        'approval_status' => 'draft',
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('designer.dashboard', absolute: false));
});

test('approved designer is redirected to the designer dashboard', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('designer');
    $user->designerProfile()->create([
        'full_name' => $user->name,
        'approval_status' => 'approved',
        'approved_at' => now(),
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('designer.dashboard', absolute: false));
});

test('draft print provider enters the dashboard and can view account status', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('print_provider');
    $user->printProvider()->create([
        'company_name' => 'Test Print Company',
        'approval_status' => 'draft',
        'is_active' => true,
    ]);

    $login = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $login->assertRedirect(route('print-provider.dashboard', absolute: false));
    $this->get('/account-status')
        ->assertOk()
        ->assertSee('مطبعة')
        ->assertSee('PRN-'.now()->format('Y').'-'.str_pad((string) $user->id, 6, '0', STR_PAD_LEFT));
});

test('inactive users cannot stay authenticated', function () {
    $user = User::factory()->create(['is_active' => false]);

    $response = $this->post('/login', [
        'locale' => 'en',
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response
        ->assertSessionHasErrors([
            'email' => 'This account is currently suspended. Please contact PALPRINTS support.',
        ])
        ->assertSessionHas('login_error_translations.email', [
            'ar' => 'هذا الحساب موقوف حاليًا. يرجى التواصل مع إدارة PALPRINTS.',
            'en' => 'This account is currently suspended. Please contact PALPRINTS support.',
        ]);
});

test('a deactivated signed-in user sees the login error in both languages', function () {
    $user = User::factory()->create(['is_active' => false]);

    $response = $this
        ->actingAs($user)
        ->withSession(['auth_locale' => 'en'])
        ->get('/dashboard');

    $this->assertGuest();
    $response
        ->assertRedirect(route('login', absolute: false))
        ->assertSessionHas(
            'error',
            'You were signed out because your account is currently inactive. Please contact PALPRINTS support.'
        )
        ->assertSessionHas('login_error_translations.general', [
            'ar' => 'تم تسجيل خروجك لأن حسابك غير نشط حاليًا. يرجى التواصل مع إدارة PALPRINTS.',
            'en' => 'You were signed out because your account is currently inactive. Please contact PALPRINTS support.',
        ]);
});

test('a customer cannot open an admin dashboard', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('customer');

    $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
});
