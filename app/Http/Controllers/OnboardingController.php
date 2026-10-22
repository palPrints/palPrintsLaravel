<?php

namespace App\Http\Controllers;

use App\Models\DesignerProfile;
use App\Services\ApprovalSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OnboardingController extends Controller
{
    public function submit(Request $request, ApprovalSubmissionService $submission): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->supportsOnboarding(), 403);

        $profile = $user->roleProfile();

        if ($profile instanceof DesignerProfile && $missing = $profile->missingRequiredFields()) {
            throw ValidationException::withMessages([
                'profile' => 'أكمل البيانات التالية واحفظها قبل إرسال طلب الاعتماد: '.implode('، ', $missing).'.',
            ]);
        }

        if (! $profile || ! $user->hasCompletedRoleProfile()) {
            throw ValidationException::withMessages([
                'profile' => 'يجب إكمال بيانات ملف النشاط قبل إرسال طلب الاعتماد.',
            ]);
        }

        if (! in_array($user->approvalStatus(), ApprovalSubmissionService::SUBMITTABLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'profile' => 'لا يمكن إرسال طلب جديد في حالة الحساب الحالية.',
            ]);
        }

        $approvalRequest = $submission->submit($user, $request->ip(), $request->userAgent());

        return redirect()
            ->route($user->dashboardRouteName())
            ->with('success', 'تم إرسال طلب الاعتماد بنجاح. رقم الطلب: '.$approvalRequest->request_number);
    }
}
