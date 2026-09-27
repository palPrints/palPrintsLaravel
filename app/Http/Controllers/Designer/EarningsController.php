<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Services\WithdrawalService;
use App\Support\PlatformSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EarningsController extends Controller
{
    private const TRANSACTION_TYPES = [
        'design_profit' => 'أرباح تصميم',
        'withdrawal' => 'طلب سحب',
        'refund' => 'استرجاع',
        'adjustment' => 'تسوية',
    ];

    public function __invoke(Request $request): View
    {
        $wallet = $request->user()->wallet;
        $transactions = $wallet?->walletTransactions()->latest()->limit(100)->get() ?? collect();

        return view('designer.earnings', [
            'wallet' => $wallet,
            'transactions' => $transactions,
            'transactionTypes' => self::TRANSACTION_TYPES,
        ]);
    }

    public function withdraw(Request $request, WithdrawalService $withdrawals): JsonResponse
    {
        $minimum = (float) PlatformSettings::get('fees', 'minimum_withdrawal', 100);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:'.$minimum],
            'method' => ['required', 'in:bank,wallet'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'amount.min' => 'الحد الأدنى للسحب هو ₪'.$minimum.'.',
        ]);

        $withdrawal = $withdrawals->request(
            $request->user(),
            (float) $validated['amount'],
            $validated['method'],
            ['notes' => $validated['notes'] ?? null],
            $minimum,
        );

        return response()->json([
            'id' => $withdrawal->id,
            'message' => 'تم حفظ طلب السحب بنجاح.',
        ], 201);
    }
}
