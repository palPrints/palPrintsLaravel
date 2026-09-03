<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ApprovalRequestController extends Controller
{
    public function update(Request $request, ApprovalRequest $approvalRequest): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['under_review', 'approved', 'changes_requested', 'rejected'])],
            'admin_notes' => [
                Rule::requiredIf(in_array($request->input('action'), ['changes_requested', 'rejected'], true)),
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $allowedActions = match ($approvalRequest->status) {
            'submitted' => ['under_review', 'approved', 'changes_requested', 'rejected'],
            'under_review' => ['approved', 'changes_requested', 'rejected'],
            default => [],
        };

        if (! in_array($validated['action'], $allowedActions, true)) {
            throw ValidationException::withMessages([
                'action' => 'لا يمكن تنفيذ هذا الإجراء على طلب بحالته الحالية.',
            ]);
        }

        $accountOwner = $approvalRequest->user;
        $profile = $accountOwner->roleProfile();

        if (! $profile) {
            throw ValidationException::withMessages([
                'action' => 'ملف النشاط المرتبط بهذا الطلب غير موجود.',
            ]);
        }

        DB::transaction(function () use ($request, $validated, $approvalRequest, $accountOwner, $profile): void {
            $oldStatus = $approvalRequest->status;
            $newStatus = $validated['action'];
            $reviewedAt = now();
            $adminNotes = $validated['admin_notes'] ?? null;

            $approvalRequest->update([
                'status' => $newStatus,
                'reviewed_at' => $reviewedAt,
                'reviewed_by' => $request->user()->id,
                'admin_notes' => $adminNotes,
            ]);

            $profile->update([
                'approval_status' => $newStatus,
                'reviewed_at' => $reviewedAt,
                'approved_at' => $newStatus === 'approved' ? $reviewedAt : null,
                'approved_by' => $newStatus === 'approved' ? $request->user()->id : null,
                'rejection_reason' => in_array($newStatus, ['changes_requested', 'rejected'], true) ? $adminNotes : null,
                'admin_notes' => $adminNotes,
            ]);

            if ($newStatus === 'approved') {
                Wallet::firstOrCreate(['user_id' => $accountOwner->id]);
            }

            Notification::create([
                'user_id' => $accountOwner->id,
                'type' => 'approval.'.$newStatus,
                'title' => $this->notificationTitle($newStatus),
                'message' => $adminNotes ?: $this->notificationMessage($newStatus),
                'link' => route('account.status'),
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'approval.reviewed',
                'description' => 'تم تحديث حالة طلب اعتماد الحساب '.$approvalRequest->request_number.'.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'old_values' => ['status' => $oldStatus],
                'new_values' => [
                    'status' => $newStatus,
                    'account_user_id' => $accountOwner->id,
                    'request_number' => $approvalRequest->request_number,
                ],
            ]);
        });

        return back()->with('success', 'تم تحديث حالة الطلب بنجاح.');
    }

    private function notificationTitle(string $status): string
    {
        return match ($status) {
            'under_review' => 'طلبك قيد المراجعة',
            'approved' => 'تم اعتماد حسابك',
            'changes_requested' => 'مطلوب تعديل بيانات الحساب',
            'rejected' => 'تعذر اعتماد الحساب',
        };
    }

    private function notificationMessage(string $status): string
    {
        return match ($status) {
            'under_review' => 'بدأت الإدارة مراجعة طلب اعتماد حسابك.',
            'approved' => 'تم اعتماد حسابك وأصبحت ميزات النشاط متاحة لك.',
            'changes_requested' => 'يرجى مراجعة بيانات ملف النشاط وإعادة إرسال الطلب.',
            'rejected' => 'تم رفض طلب الاعتماد. راجع ملاحظات الإدارة للمزيد من التفاصيل.',
        };
    }
}
