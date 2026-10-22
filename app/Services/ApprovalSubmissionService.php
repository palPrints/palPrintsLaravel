<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Sends a business account's profile to the admins for approval. */
class ApprovalSubmissionService
{
    /** Statuses from which a (new) request can be sent. */
    public const SUBMITTABLE_STATUSES = ['draft', 'changes_requested', 'rejected'];

    public function canSubmit(User $user): bool
    {
        return $user->supportsOnboarding()
            && $user->roleProfile() !== null
            && $user->hasCompletedRoleProfile()
            && in_array($user->approvalStatus(), self::SUBMITTABLE_STATUSES, true);
    }

    public function submit(User $user, ?string $ipAddress = null, ?string $userAgent = null): ApprovalRequest
    {
        /** @var Model $profile */
        $profile = $user->roleProfile();

        return DB::transaction(function () use ($user, $profile, $ipAddress, $userAgent): ApprovalRequest {
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

            $roleLabel = match ($user->primaryRole()) {
                'designer' => 'مصمم',
                'print_provider' => 'مطبعة',
                default => 'حساب',
            };

            User::role('admin')->where('is_active', true)->get()->each(
                fn (User $admin) => Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'approval.pending',
                    'title' => 'طلب اعتماد جديد من '.$roleLabel.': '.$user->name,
                    'message' => 'أرسل '.$user->name.' ('.$roleLabel.') طلب اعتماد بانتظار المراجعة. رقم الطلب: '.$approvalRequest->request_number,
                    'link' => route('admin.users', ['open' => $user->id]),
                ])
            );

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'approval.submitted',
                'description' => 'تم إرسال طلب اعتماد الحساب.',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'new_values' => [
                    'request_number' => $approvalRequest->request_number,
                    'status' => 'submitted',
                ],
            ]);

            return $approvalRequest;
        });
    }
}
