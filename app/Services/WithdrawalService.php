<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WithdrawalService
{
    /**
     * Move the requested amount from the wallet's available balance to pending
     * and record both the withdrawal request and its wallet transaction.
     * Money is handled in cents to avoid float rounding errors.
     */
    public function request(User $user, float $amount, string $method, array $accountDetails = [], float $minimum = 1): WithdrawalRequest
    {
        return DB::transaction(function () use ($user, $amount, $method, $accountDetails, $minimum) {
            $wallet = $user->wallet()->lockForUpdate()->first();

            if (! $wallet) {
                throw ValidationException::withMessages([
                    'amount' => 'لا توجد محفظة مرتبطة بهذا الحساب.',
                ]);
            }

            $cents = (int) round($amount * 100);
            $availableCents = (int) round(((float) $wallet->available_balance) * 100);

            if ($cents < (int) round($minimum * 100)) {
                throw ValidationException::withMessages([
                    'amount' => 'الحد الأدنى للسحب هو $'.number_format($minimum, 2).'.',
                ]);
            }

            if ($cents > $availableCents) {
                throw ValidationException::withMessages([
                    'amount' => 'المبلغ المطلوب أكبر من الرصيد المتاح للسحب.',
                ]);
            }

            $availableAfter = ($availableCents - $cents) / 100;

            $withdrawal = WithdrawalRequest::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'amount' => $cents / 100,
                'method' => $method,
                'account_details' => $accountDetails,
                'status' => 'pending',
                'reference_id' => 'WDR-'.strtoupper(Str::random(10)),
            ]);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'withdrawal',
                'amount' => $cents / 100,
                'balance_after' => $availableAfter,
                'description' => 'طلب سحب أرباح',
                'status' => 'requested',
                'reference_id' => $withdrawal->reference_id,
            ]);

            $wallet->update([
                'available_balance' => $availableAfter,
                'pending_balance' => ((int) round(((float) $wallet->pending_balance) * 100) + $cents) / 100,
                'last_updated' => now(),
            ]);

            return $withdrawal;
        });
    }
}
