<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use App\Services\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentController extends Controller
{
    private const WITHDRAWAL_STATES = [
        'pending' => ['pending', 'قيد المراجعة'],
        'approved' => ['completed', 'تم الاعتماد'],
        'completed' => ['completed', 'تم الاعتماد'],
        'rejected' => ['failed', 'مرفوض'],
    ];

    private const TRANSACTION_STATES = [
        'complete' => ['completed', 'مكتملة'],
        'completed' => ['completed', 'مكتملة'],
        'available' => ['completed', 'متاحة للسحب'],
        'pending' => ['pending', 'معلقة'],
        'requested' => ['pending', 'في طلب السحب'],
        'transferring' => ['processing', 'قيد التحويل'],
        'rejected' => ['failed', 'مرفوضة'],
    ];

    private const TRANSACTION_TYPES = [
        'design_profit' => ['sale-type', 'أرباح تصميم'],
        'withdrawal' => ['payout-type', 'طلب سحب'],
        'commission' => ['commission-type', 'عمولة منصة'],
        'refund' => ['refund-type', 'استرداد'],
    ];

    private const METHODS = [
        'bank' => ['bi-bank', 'تحويل بنكي'],
        'wallet' => ['bi-phone', 'محفظة إلكترونية'],
    ];

    public function index(): View
    {
        $withdrawals = WithdrawalRequest::query()
            ->with('user:id,name,email')
            ->latest()
            ->get()
            ->map(function (WithdrawalRequest $withdrawal) {
                [$class, $label] = self::WITHDRAWAL_STATES[$withdrawal->status] ?? ['pending', $withdrawal->status];
                [$icon, $method] = self::METHODS[$withdrawal->method] ?? ['bi-cash-coin', $withdrawal->method];

                return [
                    'id' => $withdrawal->id,
                    'name' => $withdrawal->user?->name ?? 'مستخدم محذوف',
                    'email' => $withdrawal->user?->email,
                    'role' => $withdrawal->user?->hasRole('print_provider') ? 'print_provider' : 'designer',
                    'amount' => (float) $withdrawal->amount,
                    'date' => $withdrawal->created_at->locale('ar')->translatedFormat('j F Y'),
                    'iso' => $withdrawal->created_at->toDateString(),
                    'stateClass' => $class,
                    'stateLabel' => $label,
                    'pending' => $withdrawal->status === 'pending',
                    'icon' => $icon,
                    'method' => $method,
                    'reviewUrl' => route('admin.payments.withdrawals.review', $withdrawal),
                ];
            });

        $transactions = WalletTransaction::query()
            ->with('wallet.user:id,name')
            ->latest()
            ->limit(100)
            ->get()
            ->map(function (WalletTransaction $transaction) {
                [$class, $label] = self::TRANSACTION_STATES[$transaction->status] ?? ['pending', $transaction->status];
                [$typeClass, $type] = self::TRANSACTION_TYPES[$transaction->type] ?? ['sale-type', $transaction->type];

                return [
                    'reference' => $transaction->reference_id ?: 'TRX-'.$transaction->id,
                    'name' => $transaction->wallet?->user?->name ?? '—',
                    'amount' => (float) $transaction->amount,
                    'date' => $transaction->created_at->locale('ar')->translatedFormat('j F Y'),
                    'iso' => $transaction->created_at->toDateString(),
                    'stateClass' => $class,
                    'stateLabel' => $label,
                    'typeClass' => $typeClass,
                    'type' => $type,
                    'due' => in_array($class, ['pending', 'failed'], true),
                ];
            });

        return view('admin.payments', [
            'withdrawals' => $withdrawals,
            'transactions' => $transactions,
            'summary' => [
                'total' => (float) WalletTransaction::sum('amount'),
                'commission' => (float) WalletTransaction::where('type', 'commission')->sum('amount'),
                'pendingWithdrawals' => WithdrawalRequest::where('status', 'pending')->count(),
                'paidOut' => (float) WithdrawalRequest::whereIn('status', ['approved', 'completed'])->sum('amount'),
            ],
        ]);
    }

    public function review(Request $request, WithdrawalRequest $withdrawal, WithdrawalService $withdrawals): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $approved = $validated['action'] === 'approve';

        try {
            $approved
                ? $withdrawals->approve($withdrawal)
                : $withdrawals->reject($withdrawal, $validated['reason'] ?? null);
        } catch (ValidationException $exception) {
            return response()->json(['ok' => false, 'message' => collect($exception->errors())->flatten()->first()], 422);
        }

        $isPrintProvider = $withdrawal->user?->hasRole('print_provider') === true;

        Notification::create([
            'user_id' => $withdrawal->user_id,
            'type' => 'withdrawal_review',
            'title' => $approved ? 'تم اعتماد طلب السحب' : 'تم رفض طلب السحب',
            'message' => $approved
                ? 'تم اعتماد طلب سحب '.number_format((float) $withdrawal->amount, 2).' ₪ وسيتم تحويله إليك.'
                : 'تم رفض طلب السحب وإرجاع المبلغ إلى رصيدك المتاح.',
            'link' => route($isPrintProvider ? 'print-provider.earnings' : 'designer.earnings'),
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $approved ? 'admin.withdrawal_approved' : 'admin.withdrawal_rejected',
            'description' => 'مراجعة طلب السحب رقم '.$withdrawal->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_values' => ['withdrawal_id' => $withdrawal->id, 'amount' => (string) $withdrawal->amount],
        ]);

        return response()->json([
            'ok' => true,
            'message' => $approved ? 'تم اعتماد طلب السحب.' : 'تم رفض طلب السحب وإرجاع المبلغ للمحفظة.',
        ]);
    }
}
