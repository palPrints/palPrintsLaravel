<?php

namespace App\Http\Requests\Designer;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('designer') === true;
    }

    protected function prepareForValidation(): void
    {
        $locale = $this->input('locale') === 'en' ? 'en' : 'ar';

        app()->setLocale($locale);

        $this->merge([
            'locale' => $locale,
            'name' => trim((string) $this->input('name')),
            'email' => Str::lower(trim((string) $this->input('email'))),
            'phone' => $this->trimmedOrNull('phone'),
            'portfolio_url' => $this->trimmedOrNull('portfolio_url'),
            'skills' => $this->trimmedOrNull('skills'),
            'bio' => $this->trimmedOrNull('bio'),
        ]);
    }

    public function rules(): array
    {
        return [
            'locale' => ['required', Rule::in(['ar', 'en'])],
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($this->user()),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'portfolio_url' => ['nullable', 'string', 'url:http,https', 'max:255'],
            'skills' => ['nullable', 'string', 'max:500'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return $this->input('locale') === 'en'
            ? $this->englishMessages()
            : $this->arabicMessages();
    }

    public function skillList(): array
    {
        return collect(preg_split('/[,،]/u', (string) $this->validated('skills', '')))
            ->map(fn (string $skill): string => trim($skill))
            ->filter()
            ->unique(fn (string $skill): string => Str::lower($skill))
            ->take(12)
            ->values()
            ->all();
    }

    private function trimmedOrNull(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }

    private function englishMessages(): array
    {
        return [
            'locale.in' => 'Choose a supported language.',
            'name.required' => 'Enter your full name.',
            'name.string' => 'The full name must be text.',
            'name.min' => 'Your name must be at least 3 characters.',
            'name.max' => 'Your name must not exceed 255 characters.',
            'email.required' => 'Enter your email address.',
            'email.string' => 'The email address must be text.',
            'email.email' => 'Enter a valid email address.',
            'email.max' => 'The email address must not exceed 255 characters.',
            'email.unique' => 'This email address is already in use.',
            'phone.string' => 'The phone number must be text.',
            'phone.max' => 'The phone number must not exceed 30 characters.',
            'portfolio_url.string' => 'The portfolio link must be text.',
            'portfolio_url.url' => 'Enter a valid portfolio link starting with http or https.',
            'portfolio_url.max' => 'The portfolio link must not exceed 255 characters.',
            'skills.string' => 'The skills field must be text.',
            'skills.max' => 'The skills field must not exceed 500 characters.',
            'bio.string' => 'The bio must be text.',
            'bio.max' => 'The bio must not exceed 1,000 characters.',
            'profile_image.image' => 'Choose a valid image file.',
            'profile_image.mimes' => 'Only PNG, JPG, and WEBP images are allowed.',
            'profile_image.max' => 'The profile image must not exceed 2 MB.',
        ];
    }

    private function arabicMessages(): array
    {
        return [
            'locale.in' => 'اختر لغة مدعومة.',
            'name.required' => 'أدخل الاسم الكامل.',
            'name.string' => 'يجب أن يكون الاسم الكامل نصًا.',
            'name.min' => 'يجب أن يتكوّن الاسم من 3 أحرف على الأقل.',
            'name.max' => 'يجب ألا يتجاوز الاسم 255 حرفًا.',
            'email.required' => 'أدخل البريد الإلكتروني.',
            'email.string' => 'يجب أن يكون البريد الإلكتروني نصًا.',
            'email.email' => 'أدخل بريدًا إلكترونيًا صحيحًا.',
            'email.max' => 'يجب ألا يتجاوز البريد الإلكتروني 255 حرفًا.',
            'email.unique' => 'هذا البريد الإلكتروني مستخدم بالفعل.',
            'phone.string' => 'يجب أن يكون رقم الهاتف نصًا.',
            'phone.max' => 'يجب ألا يتجاوز رقم الهاتف 30 حرفًا.',
            'portfolio_url.string' => 'يجب أن يكون رابط معرض الأعمال نصًا.',
            'portfolio_url.url' => 'أدخل رابط معرض أعمال صحيحًا يبدأ بـ http أو https.',
            'portfolio_url.max' => 'يجب ألا يتجاوز رابط معرض الأعمال 255 حرفًا.',
            'skills.string' => 'يجب أن يكون حقل المهارات نصًا.',
            'skills.max' => 'يجب ألا يتجاوز حقل المهارات 500 حرف.',
            'bio.string' => 'يجب أن تكون النبذة نصًا.',
            'bio.max' => 'يجب ألا تتجاوز النبذة 1000 حرف.',
            'profile_image.image' => 'اختر ملف صورة صحيحًا.',
            'profile_image.mimes' => 'يُسمح بصور PNG أو JPG أو WEBP فقط.',
            'profile_image.max' => 'يجب ألا يتجاوز حجم الصورة الشخصية 2 ميجابايت.',
        ];
    }
}
