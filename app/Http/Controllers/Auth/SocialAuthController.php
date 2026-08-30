<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\SocialAuthenticationException;
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

        $requestedLocale = $request->query('locale');
        $locale = in_array($requestedLocale, ['ar', 'en'], true) ? $requestedLocale : 'ar';
        app()->setLocale($locale);

        $source = $request->query('source') === 'register' ? 'register' : 'login';
        $accountType = null;

        if ($source === 'register') {
            $accountType = (string) $request->query('account_type');

            if (! in_array($accountType, self::ACCOUNT_TYPES, true)) {
                return $this->failure(
                    'register',
                    'auth.social.account_type_required'
                );
            }

            if (! $request->boolean('terms')) {
                return $this->failure(
                    'register',
                    'auth.social.terms_required',
                    $accountType
                );
            }
        }

        if (! $this->providerIsConfigured($provider)) {
            return $this->failure(
                $source,
                'auth.social.provider_not_configured',
                $accountType,
                ['provider' => ucfirst($provider)]
            );
        }

        $intent = [
            'source' => $source,
            'account_type' => $accountType,
        ];
        if (in_array($requestedLocale, ['ar', 'en'], true)) {
            $intent['locale'] = $locale;
        }
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
                'auth.social.connection_failed',
                $accountType,
                ['provider' => ucfirst($provider)]
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
        $locale = in_array($intent['locale'] ?? null, ['ar', 'en'], true)
            ? $intent['locale']
            : 'ar';
        app()->setLocale($locale);

        if ($request->filled('error')) {
            $request->session()->forget('social_auth');

            return $this->failure(
                $source,
                'auth.social.cancelled',
                $accountType,
                ['provider' => ucfirst($provider)]
            );
        }

        if (! $this->providerIsConfigured($provider)) {
            $request->session()->forget('social_auth');

            return $this->failure(
                $source,
                'auth.social.settings_incomplete',
                $accountType,
                ['provider' => ucfirst($provider)]
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
            $request->session()->put('auth_locale', $locale);
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
        } catch (SocialAuthenticationException $exception) {
            $request->session()->forget('social_auth');

            return $this->failure(
                $source,
                $exception->translationKey,
                $accountType,
                $exception->replacements
            );
        } catch (ValidationException $exception) {
            $request->session()->forget('social_auth');
            $message = collect($exception->errors())->flatten()->first()
                ?? trans('auth.social.unable_to_complete');

            return $this->failureMessage($source, $message, $accountType);
        } catch (Throwable $exception) {
            report($exception);
            $request->session()->forget('social_auth');

            return $this->failure(
                $source,
                'auth.social.provider_login_failed',
                $accountType,
                ['provider' => ucfirst($provider)]
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
            throw new SocialAuthenticationException('auth.social.provider_id_missing');
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
            throw new SocialAuthenticationException(
                'auth.social.email_unverified',
                ['provider' => ucfirst($provider)]
            );
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
            throw new SocialAuthenticationException('auth.social.login_account_missing');
        }

        if ($user) {
            $this->assertAccountIsActive($user);
        }

        $wasCreated = false;

        if (! $user) {
            if (! in_array($accountType, self::ACCOUNT_TYPES, true)) {
                throw new SocialAuthenticationException('auth.social.role_session_expired');
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
            throw new SocialAuthenticationException(
                'auth.social.already_linked',
                ['provider' => ucfirst($provider)]
            );
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
            throw new SocialAuthenticationException('auth.social.inactive');
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
        string $translationKey,
        ?string $accountType = null,
        array $replacements = []
    ): RedirectResponse {
        $translations = [
            'ar' => trans($translationKey, $replacements, 'ar'),
            'en' => trans($translationKey, $replacements, 'en'),
        ];
        $locale = app()->getLocale() === 'en' ? 'en' : 'ar';

        return $this->failureMessage(
            $source,
            $translations[$locale],
            $accountType,
            $translations
        );
    }

    /**
     * @param  array{ar: string, en: string}|null  $translations
     */
    private function failureMessage(
        string $source,
        string $message,
        ?string $accountType = null,
        ?array $translations = null
    ): RedirectResponse {
        $route = $source === 'register' ? 'register' : 'login';
        $response = redirect()->route($route)->withErrors(['social' => $message]);

        if ($route === 'register' && in_array($accountType, self::ACCOUNT_TYPES, true)) {
            $response->withInput(['account_type' => $accountType, 'terms' => '1']);
        }

        if ($route === 'login' && $translations) {
            $response->with('login_error_translations', ['social' => $translations]);
        }

        return $response;
    }
}
