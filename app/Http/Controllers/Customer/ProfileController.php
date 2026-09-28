<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('customer.profile', [
            'customer' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'fullName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ], [
            'fullName.required' => 'أدخلي الاسم الكامل.',
            'email.required' => 'أدخلي البريد الإلكتروني.',
            'email.email' => 'أدخلي بريدًا إلكترونيًا صحيحًا.',
            'email.unique' => 'هذا البريد الإلكتروني مستخدم بالفعل.',
            'avatar.image' => 'يجب أن تكون الصورة بصيغة صحيحة.',
            'avatar.max' => 'حجم الصورة كبير جدًا.',
        ]);

        $user->name = trim($validated['fullName']);
        $user->email = mb_strtolower(trim($validated['email']));
        $user->phone = $validated['phone'] ?? null;

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('avatar')) {
            $oldAvatarPath = $user->avatar_path;
            $user->avatar_path = $request->file('avatar')->store('customer/avatars', 'public');

            if ($oldAvatarPath) {
                Storage::disk('public')->delete($oldAvatarPath);
            }
        }

        $user->save();

        return redirect()->route('customer.profile')->with('status', 'profile-updated');
    }
}
