<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Services\WithdrawalService;
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
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', 'in:bank,wallet'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $withdrawal = $withdrawals->request(
            $request->user(),
            (float) $validated['amount'],
            $validated['method'],
            ['notes' => $validated['notes'] ?? null],
        );

        return response()->json([
            'id' => $withdrawal->id,
            'message' => 'تم حفظ طلب السحب بنجاح.',
        ], 201);
    }
}
