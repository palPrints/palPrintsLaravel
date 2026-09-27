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

    /** Admin approval: the pending amount leaves the wallet for good. */
    public function approve(WithdrawalRequest $withdrawal): WithdrawalRequest
    {
        return $this->review($withdrawal, true, null);
    }

    /** Admin rejection: the pending amount goes back to the available balance. */
    public function reject(WithdrawalRequest $withdrawal, ?string $reason = null): WithdrawalRequest
    {
        return $this->review($withdrawal, false, $reason);
    }

    private function review(WithdrawalRequest $withdrawal, bool $approved, ?string $reason): WithdrawalRequest
    {
        return DB::transaction(function () use ($withdrawal, $approved, $reason) {
            $withdrawal = WithdrawalRequest::whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();

            if ($withdrawal->status !== 'pending') {
                throw ValidationException::withMessages([
                    'withdrawal' => 'تمت مراجعة هذا الطلب مسبقًا.',
                ]);
            }

            $wallet = $withdrawal->wallet()->lockForUpdate()->firstOrFail();
            $cents = (int) round(((float) $withdrawal->amount) * 100);
            $pendingCents = max((int) round(((float) $wallet->pending_balance) * 100) - $cents, 0);

            $walletChanges = ['pending_balance' => $pendingCents / 100, 'last_updated' => now()];

            if ($approved) {
                $walletChanges['total_withdrawn'] = ((int) round(((float) $wallet->total_withdrawn) * 100) + $cents) / 100;
            } else {
                $walletChanges['available_balance'] = ((int) round(((float) $wallet->available_balance) * 100) + $cents) / 100;
            }

            $wallet->update($walletChanges);

            $withdrawal->update($approved
                ? ['status' => 'approved', 'approved_at' => now(), 'completed_at' => now()]
                : ['status' => 'rejected', 'rejection_reason' => $reason]);

            WalletTransaction::where('wallet_id', $wallet->id)
                ->where('reference_id', $withdrawal->reference_id)
                ->update(['status' => $approved ? 'complete' : 'rejected']);

            return $withdrawal;
        });
    }
}
