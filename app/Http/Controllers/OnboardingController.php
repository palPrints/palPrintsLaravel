<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\AuditLog;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OnboardingController extends Controller
{
    public function submit(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->supportsOnboarding(), 403);

        $profile = $user->roleProfile();

        if (! $profile || ! $user->hasCompletedRoleProfile()) {
            throw ValidationException::withMessages([
                'profile' => 'يجب إكمال بيانات ملف النشاط قبل إرسال طلب الاعتماد.',
            ]);
        }

        if (! in_array($user->approvalStatus(), ['draft', 'changes_requested', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'profile' => 'لا يمكن إرسال طلب جديد في حالة الحساب الحالية.',
            ]);
        }

        $approvalRequest = DB::transaction(function () use ($request, $user, $profile): ApprovalRequest {
            $submittedAt = now();
            $approvalRequest = ApprovalRequest::create([
                'user_id' => $user->id,
                'role' => $user->primaryRole(),
                'request_number' => 'APR-'.$submittedAt->format('Ymd').'-'.Str::upper((string) Str::ulid()),
                'status' => 'submitted',
                'profile_snapshot' => Arr::except($profile->toArray(), ['api_key']),
                'submitted_at' => $submittedAt,
            ]);

            $profile->update([
                'approval_status' => 'submitted',
                'submitted_at' => $submittedAt,
                'reviewed_at' => null,
                'approved_at' => null,
                'approved_by' => null,
                'rejection_reason' => null,
                'admin_notes' => null,
            ]);

            Notification::create([
                'user_id' => $user->id,
                'type' => 'approval.submitted',
                'title' => 'تم إرسال طلب الاعتماد',
                'message' => 'استلمنا طلبك، وستتم مراجعته من الإدارة.',
                'link' => route('account.status'),
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'approval.submitted',
                'description' => 'تم إرسال طلب اعتماد الحساب.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'new_values' => [
                    'request_number' => $approvalRequest->request_number,
                    'status' => 'submitted',
                ],
            ]);

            return $approvalRequest;
        });

        return redirect()
            ->route($user->dashboardRouteName())
            ->with('success', 'تم إرسال طلب الاعتماد بنجاح. رقم الطلب: '.$approvalRequest->request_number);
    }
}
