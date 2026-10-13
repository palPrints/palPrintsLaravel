<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Designer\UpdateProfileRequest;
use App\Models\AuditLog;
use App\Models\Design;
use App\Models\DesignerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class ProfileController extends Controller
{
    private const EXTRA_FIELDS = ['job_title', 'experience', 'specialization'];

    public function show(Request $request): View
    {
        $user = $request->user()->loadMissing('designerProfile');
        $profile = $user->designerProfile ?? new DesignerProfile([
            'full_name' => $user->name,
            'approval_status' => 'draft',
            'skills' => [],
        ]);

        $designs = Design::query()->where('designer_id', $user->id);
        $designStats = [
            'draft' => (clone $designs)->where('status', 'draft')->count(),
            'published' => (clone $designs)->where('status', 'published')->count(),
            'review' => (clone $designs)->whereIn('status', ['review', 'submitted'])->count(),
            'rejected' => (clone $designs)->where('status', 'rejected')->count(),
        ];

        $accountType = match ($user->primaryRole()) {
            'designer' => ['ar' => 'مصمم', 'en' => 'Designer'],
            default => ['ar' => 'حساب مستخدم', 'en' => 'User account'],
        };

        return view('designer.profile', [
            'user' => $user,
            'profile' => $profile,
            'displayName' => $profile->full_name ?: $user->name,
            'profileSkills' => collect($profile->skills ?? [])->filter()->values(),
            'approval' => $this->approvalPresentation($profile->approval_status ?? 'draft'),
            'portfolioUrl' => $this->safeHttpUrl($profile->portfolio_url),
            'avatarUrl' => $this->avatarUrl($profile->profile_image),
            'profileExtra' => [
                'job_title' => $profile->getAttribute('job_title'),
                'experience' => $profile->getAttribute('experience'),
                'specialization' => $profile->getAttribute('specialization') ?: $profile->bio,
            ],
            'designStats' => $designStats,
            'accountType' => $accountType,
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $skills = $request->skillList();
        $user = $request->user();
        $profile = $user->designerProfile()->firstOrNew();
        $oldImagePath = $profile->profile_image;
        $newImagePath = $request->file('profile_image')
            ? $request->file('profile_image')->store('designer/profile-images', 'public')
            : null;

        try {
            DB::transaction(function () use ($request, $validated, $skills, $user, $profile, $newImagePath): void {
                $oldValues = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'bio' => $profile->bio,
                    'skills' => $profile->skills ?? [],
                    'portfolio_url' => $profile->portfolio_url,
                    'profile_image' => $profile->profile_image,
                ];

                $user->fill([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                ]);

                if ($user->isDirty('email')) {
                    $user->email_verified_at = null;
                }

                $user->save();

                // Without a dedicated "specialization" column the text lives in bio.
                $about = $validated['specialization'] ?? $validated['bio'];

                $profile->fill([
                    'full_name' => $validated['name'],
                    'bio' => $about,
                    'skills' => $skills,
                    'portfolio_url' => $validated['portfolio_url'],
                ]);

                // Optional profile fields are stored only once their columns exist in designer_profiles.
                foreach (self::EXTRA_FIELDS as $column) {
                    if (Schema::hasColumn('designer_profiles', $column)) {
                        $profile->forceFill([$column => $validated[$column] ?? null]);
                    }
                }

                if ($newImagePath !== null) {
                    $profile->profile_image = $newImagePath;
                }

                $profile->profile_completed_at = $profile->isComplete() ? now() : null;

                $profile->save();

                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'designer.profile_updated',
                    'description' => 'تم تحديث الملف الشخصي للمصمم.',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old_values' => $oldValues,
                    'new_values' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'bio' => $profile->bio,
                        'skills' => $profile->skills ?? [],
                        'portfolio_url' => $profile->portfolio_url,
                        'profile_image' => $profile->profile_image,
                    ],
                ]);
            });
        } catch (Throwable $exception) {
            if ($newImagePath !== null) {
                Storage::disk('public')->delete($newImagePath);
            }

            throw $exception;
        }

        if ($newImagePath !== null && $this->isLocalImagePath($oldImagePath)) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return redirect()
            ->route('designer.profile')
            ->with('profile_status', 'updated');
    }

    private function approvalPresentation(string $status): array
    {
        return match ($status) {
            'approved' => [
                'status' => $status,
                'class' => 'is-approved',
                'icon' => 'bi-patch-check-fill',
                'label_ar' => 'مصمم معتمد',
                'label_en' => 'Verified designer',
            ],
            'rejected', 'changes_requested' => [
                'status' => $status,
                'class' => 'is-rejected',
                'icon' => 'bi-x-circle-fill',
                'label_ar' => $status === 'rejected' ? 'مرفوض بسبب خلل في البيانات' : 'يحتاج إلى تعديل البيانات',
                'label_en' => $status === 'rejected' ? 'Rejected due to data issues' : 'Data changes requested',
            ],
            'submitted', 'under_review' => [
                'status' => $status,
                'class' => 'is-reviewing',
                'icon' => 'bi-hourglass-split',
                'label_ar' => 'قيد المراجعة',
                'label_en' => 'Under review',
            ],
            default => [
                'status' => 'draft',
                'class' => 'is-draft',
                'icon' => 'bi-info-circle-fill',
                'label_ar' => 'الملف غير مكتمل',
                'label_en' => 'Profile incomplete',
            ],
        };
    }

    private function avatarUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $remoteUrl = $this->safeHttpUrl($path);

        if ($remoteUrl !== null) {
            return $remoteUrl;
        }

        $localPath = ltrim($path, '/');

        return Storage::disk('public')->exists($localPath)
            ? Storage::disk('public')->url($localPath)
            : null;
    }

    private function safeHttpUrl(?string $url): ?string
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)
            ? $url
            : null;
    }

    private function isLocalImagePath(?string $path): bool
    {
        return filled($path) && $this->safeHttpUrl($path) === null;
    }
}
