<?php

namespace App\Http\Controllers\PrintProvider;

use App\Support\PayoutAccount;
use App\Http\Controllers\Controller;
use App\Services\WithdrawalService;
use App\Support\PlatformSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EarningsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $wallet = $user->wallet;

        return view('printProvider.earnings', [
            'wallet' => $wallet,
            'transactions' => $wallet?->walletTransactions()->where('type', '!=', 'withdrawal')->latest()->limit(100)->get() ?? collect(),
            'withdrawals' => $user->withdrawalRequests()->latest()->limit(50)->get(),
            'minimumWithdrawal' => $this->minimumWithdrawal(),
        ]);
    }

    public function withdraw(Request $request, WithdrawalService $withdrawals): JsonResponse
    {
        $minimum = $this->minimumWithdrawal();

        $validated = PayoutAccount::validate($request, $minimum);

        $withdrawal = $withdrawals->request(
            $request->user(),
            (float) $validated['amount'],
            $validated['method'],
            ['account' => $validated['account'], 'holder' => $validated['holder']],
            $minimum,
        );

        return response()->json([
            'id' => $withdrawal->id,
            'message' => 'تم إرسال طلب السحب بنجاح.',
        ], 201);
    }

    private function minimumWithdrawal(): float
    {
        return (float) PlatformSettings::get('fees', 'minimum_withdrawal', 100);
    }
}
