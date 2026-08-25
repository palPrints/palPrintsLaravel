<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\AccountProvisioningService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as ProviderUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;

class SocialAuthController extends Controller
{
    private const PROVIDERS = ['google', 'apple'];

    private const ACCOUNT_TYPES = ['customer', 'designer', 'print_provider'];

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        $source = $request->query('source') === 'register' ? 'register' : 'login';
        $accountType = null;

        if ($source === 'register') {
            $accountType = (string) $request->query('account_type');

            if (! in_array($accountType, self::ACCOUNT_TYPES, true)) {
                return $this->failure(
                    'register',
                    'اختر نوع الحساب أولًا قبل المتابعة باستخدام Google أو Apple.'
                );
            }

            if (! $request->boolean('terms')) {
                return $this->failure(
                    'register',
                    'يجب الموافقة على الشروط وسياسة الخصوصية قبل إنشاء الحساب.',
                    $accountType
                );
            }
        }

        if (! $this->providerIsConfigured($provider)) {
            return $this->failure(
                $source,
                'تسجيل الدخول بواسطة '.ucfirst($provider).' غير مهيأ بعد. أضف بيانات المزوّد إلى ملف البيئة.',
                $accountType
            );
        }

        $intent = [
            'source' => $source,
            'account_type' => $accountType,
        ];
        $request->session()->put('social_auth', $intent);

        try {
            $response = Socialite::driver($provider)->redirect();

            if ($provider === 'apple') {
                $state = (string) $request->session()->get('state');

                if ($state !== '') {
                    Cache::put($this->intentCacheKey($state), $intent, now()->addMinutes(10));
                }
            }

            return $response;
        } catch (Throwable $exception) {
            report($exception);
            $request->session()->forget('social_auth');

            return $this->failure(
                $source,
                'تعذر بدء الاتصال مع '.ucfirst($provider).'. حاول مرة أخرى.',
                $accountType
            );
        }
    }

    public function callback(
        Request $request,
        string $provider,
        AccountProvisioningService $accounts
    ): RedirectResponse {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        $intent = $request->session()->get('social_auth', []);

        if ($provider === 'apple' && $request->filled('state')) {
            $cachedIntent = Cache::pull(
                $this->intentCacheKey((string) $request->input('state'))
            );

            if (is_array($cachedIntent)) {
                $intent = $cachedIntent;
            }
        }
        $source = ($intent['source'] ?? null) === 'register' ? 'register' : 'login';
        $accountType = $intent['account_type'] ?? null;

        if ($request->filled('error')) {
            $request->session()->forget('social_auth');

            return $this->failure(
                $source,
                'تم إلغاء المتابعة بواسطة '.ucfirst($provider).'.',
                $accountType
            );
        }

        if (! $this->providerIsConfigured($provider)) {
            $request->session()->forget('social_auth');

            return $this->failure(
                $source,
                'إعدادات '.ucfirst($provider).' غير مكتملة.',
                $accountType
            );
        }

        try {
            $providerUser = Socialite::driver($provider)->user();
            [$user, $wasCreated, $wasLinked] = $this->resolveUser(
                $accounts,
                $provider,
                $providerUser,
                $source,
                $accountType
            );

            if ($wasCreated) {
                event(new Registered($user));
            }

            Auth::guard('web')->login($user);
            $request->session()->regenerate();
            $request->session()->forget('social_auth');

            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ])->saveQuietly();

            AuditLog::create([
                'user_id' => $user->id,
                'action' => $wasCreated ? 'account.social_registered' : 'account.social_logged_in',
                'description' => $wasCreated
                    ? 'تم إنشاء الحساب وتسجيل الدخول بواسطة '.ucfirst($provider).'.'
                    : 'تم تسجيل الدخول بواسطة '.ucfirst($provider).'.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'new_values' => [
                    'provider' => $provider,
                    'provider_link_created' => $wasLinked,
                ],
            ]);

            return redirect()->intended(route($user->dashboardRouteName(), absolute: false));
        } catch (ValidationException $exception) {
            $request->session()->forget('social_auth');
            $message = collect($exception->errors())->flatten()->first()
                ?? 'تعذر إكمال تسجيل الدخول.';

            return $this->failure($source, $message, $accountType);
        } catch (Throwable $exception) {
            report($exception);
            $request->session()->forget('social_auth');

            return $this->failure(
                $source,
                'تعذر إكمال تسجيل الدخول بواسطة '.ucfirst($provider).'. حاول مرة أخرى.',
                $accountType
            );
        }
    }

    private function resolveUser(
        AccountProvisioningService $accounts,
        string $provider,
        ProviderUser $providerUser,
        string $source,
        ?string $accountType
    ): array {
        $providerUserId = trim((string) $providerUser->getId());

        if ($providerUserId === '') {
            throw ValidationException::withMessages([
                'social' => 'لم يرسل مزوّد الدخول معرّف الحساب المطلوب.',
            ]);
        }

        $socialAccount = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->first();

        if ($socialAccount) {
            $user = $socialAccount->user()->firstOrFail();
            $this->assertAccountIsActive($user);

            return [$user, false, false];
        }

        $email = Str::lower(trim((string) $providerUser->getEmail()));

        if ($email === '' || ! $this->providerEmailIsVerified($providerUser, $provider)) {
            throw ValidationException::withMessages([
                'social' => 'تعذر التحقق من بريدك الإلكتروني لدى '.ucfirst($provider).'.',
            ]);
        }

        return DB::transaction(fn (): array => $this->linkOrCreateUser(
            $accounts,
            $provider,
            $providerUser,
            $providerUserId,
            $email,
            $source,
            $accountType
        ));
    }

    private function linkOrCreateUser(
        AccountProvisioningService $accounts,
        string $provider,
        ProviderUser $providerUser,
        string $providerUserId,
        string $email,
        string $source,
        ?string $accountType
    ): array {
        $socialAccount = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->lockForUpdate()
            ->first();

        if ($socialAccount) {
            $user = $socialAccount->user()->firstOrFail();
            $this->assertAccountIsActive($user);

            return [$user, false, false];
        }

        $user = User::query()
            ->where('email', $email)
            ->lockForUpdate()
            ->first();

        if (! $user && $source !== 'register') {
            throw ValidationException::withMessages([
                'social' => 'لا يوجد حساب PALPRINTS لهذا البريد. انتقل إلى إنشاء حساب واختر نوع الحساب أولًا.',
            ]);
        }

        if ($user) {
            $this->assertAccountIsActive($user);
        }

        $wasCreated = false;

        if (! $user) {
            if (! in_array($accountType, self::ACCOUNT_TYPES, true)) {
                throw ValidationException::withMessages([
                    'social' => 'انتهت جلسة اختيار نوع الحساب. اختر نوع الحساب مرة أخرى.',
                ]);
            }

            $name = trim((string) $providerUser->getName());
            if (Str::length($name) < 3) {
                $name = 'PALPRINTS User';
            }

            $user = $accounts->create([
                'name' => $name,
                'email' => $email,
                'password' => Str::random(64),
                'account_type' => $accountType,
                'locale' => app()->getLocale(),
                'email_verified_at' => now(),
            ]);
            $wasCreated = true;
        } elseif (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->saveQuietly();
        }

        $existingProviderLink = SocialAccount::query()
            ->where('user_id', $user->id)
            ->where('provider', $provider)
            ->lockForUpdate()
            ->first();

        if ($existingProviderLink) {
            throw ValidationException::withMessages([
                'social' => 'هذا الحساب مربوط مسبقًا بحساب '.ucfirst($provider).' آخر.',
            ]);
        }

        SocialAccount::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_user_id' => $providerUserId,
            'provider_email' => $email,
            'avatar_url' => $providerUser->getAvatar(),
        ]);

        return [$user, $wasCreated, true];
    }

    private function assertAccountIsActive(User $user): void
    {
        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'social' => 'هذا الحساب موقوف حاليًا. يرجى التواصل مع إدارة PALPRINTS.',
            ]);
        }
    }

    private function providerIsConfigured(string $provider): bool
    {
        $config = config('services.'.$provider, []);
        $baseIsConfigured = filled($config['client_id'] ?? null)
            && filled($config['redirect'] ?? null);

        if (! $baseIsConfigured) {
            return false;
        }

        if ($provider === 'google') {
            return filled($config['client_secret'] ?? null);
        }

        return filled($config['client_secret'] ?? null)
            || (
                filled($config['key_id'] ?? null)
                && filled($config['team_id'] ?? null)
                && filled($config['private_key'] ?? null)
            );
    }

    private function intentCacheKey(string $state): string
    {
        return 'social_auth_intent:'.hash('sha256', $state);
    }

    private function providerEmailIsVerified(ProviderUser $providerUser, string $provider): bool
    {
        $raw = $providerUser->getRaw();
        $value = $provider === 'google'
            ? ($raw['verified_email'] ?? $raw['email_verified'] ?? false)
            : ($raw['email_verified'] ?? false);

        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    private function failure(
        string $source,
        string $message,
        ?string $accountType = null
    ): RedirectResponse {
        $route = $source === 'register' ? 'register' : 'login';
        $response = redirect()->route($route)->withErrors(['social' => $message]);

        if ($route === 'register' && in_array($accountType, self::ACCOUNT_TYPES, true)) {
            $response->withInput(['account_type' => $accountType, 'terms' => '1']);
        }

        return $response;
    }
}
