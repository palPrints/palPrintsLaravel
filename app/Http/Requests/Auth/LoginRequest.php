<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $locale = in_array($this->input('locale'), ['ar', 'en'], true)
            ? $this->input('locale')
            : 'ar';

        app()->setLocale($locale);
        $this->session()->put('auth_locale', $locale);

        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
            'locale' => $locale,
        ]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Get the localized validation messages for the login form.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => trans('auth.login.email_required'),
            'email.string' => trans('auth.login.email_invalid'),
            'email.email' => trans('auth.login.email_invalid'),
            'password.required' => trans('auth.login.password_required'),
            'password.string' => trans('auth.login.password_invalid'),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $failed = $validator->failed();

        if (isset($failed['email']['Required'])) {
            $this->flashErrorTranslations('email', 'auth.login.email_required');
        } elseif (isset($failed['email'])) {
            $this->flashErrorTranslations('email', 'auth.login.email_invalid');
        }

        if (isset($failed['password']['Required'])) {
            $this->flashErrorTranslations('password', 'auth.login.password_required');
        } elseif (isset($failed['password'])) {
            $this->flashErrorTranslations('password', 'auth.login.password_invalid');
        }

        parent::failedValidation($validator);
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            $this->flashErrorTranslations('email', 'auth.failed');

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());
        $replacements = ['seconds' => $seconds];

        $this->flashErrorTranslations('email', 'auth.throttle', $replacements);

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', $replacements),
        ]);
    }

    /**
     * Store both translations so an existing error follows the page language toggle.
     *
     * @param  array<string, mixed>  $replacements
     */
    public function flashErrorTranslations(string $field, string $key, array $replacements = []): void
    {
        $translations = $this->session()->get('login_error_translations', []);
        $translations[$field] = [
            'ar' => trans($key, $replacements, 'ar'),
            'en' => trans($key, $replacements, 'en'),
        ];

        $this->session()->flash('login_error_translations', $translations);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
