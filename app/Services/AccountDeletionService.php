<?php

namespace App\Services;

use App\Models\Address;
use App\Models\CartItem;
use App\Models\Design;
use App\Models\DesignFavorite;
use App\Models\Notification;
use App\Models\OrderItem;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * "Delete my account".
 *
 * An account that has no history is removed. One that took part in orders, payments or withdrawals cannot be removed
 * (the database keeps those records), so it is closed instead: personal details are wiped, sign-in is no longer
 * possible, and its shop or designs stop selling. Either way the person is gone from the site.
 * An account that still owes or is owed something (running orders, money in the wallet) is not closed at all.
 */
class AccountDeletionService
{
    /** Order statuses that are over. */
    private const FINAL_STATUSES = ['delivered', 'completed', 'cancelled', 'rejected'];

    /**
     * Why the account cannot be closed right now (Arabic, shown to the person). Empty when it can.
     *
     * @return array<int, string>
     */
    public function blockers(User $user): array
    {
        if ($user->hasRole('admin')) {
            return ['حساب الإدارة لا يُحذف من هنا. اطلب ذلك من مسؤول آخر في المنصة.'];
        }

        $reasons = [];
        $running = fn ($query) => $query->whereNotIn('status', self::FINAL_STATUSES);

        if ($user->orders()->tap($running)->exists()) {
            $reasons[] = 'لديك طلبات لم تكتمل بعد. انتظر حتى تصل أو تُلغى ثم حاول مجددًا.';
        }

        $branchIds = $user->printProvider?->branches()->pluck('id') ?? collect();
        if ($branchIds->isNotEmpty() && OrderItem::whereIn('print_provider_branch_id', $branchIds)->whereHas('order', $running)->exists()) {
            $reasons[] = 'لدى مطبعتك طلبات قيد التنفيذ. أنهِها أولًا ثم حاول مجددًا.';
        }

        if (OrderItem::where('designer_id', $user->id)->whereHas('order', $running)->exists()) {
            $reasons[] = 'بعض تصاميمك مطلوبة في طلبات لم تكتمل بعد. انتظر حتى تنتهي ثم حاول مجددًا.';
        }

        $wallet = $user->wallet;
        if (($wallet && ((float) $wallet->available_balance > 0 || (float) $wallet->pending_balance > 0))
            || $user->withdrawalRequests()->where('status', 'pending')->exists()) {
            $reasons[] = 'في محفظتك رصيد أو طلب سحب قيد المراجعة. اسحب أرباحك أو تواصل مع الدعم قبل حذف الحساب.';
        }

        return $reasons;
    }

    /** Removes the account, or closes it when the database must keep its records. Callers check blockers() first. */
    public function delete(User $user): void
    {
        $personalFiles = $this->personalFiles($user);
        $designFiles = $this->designFiles($user);

        try {
            DB::transaction(fn () => $user->delete());
        } catch (QueryException $exception) {
            // Anything other than "other records still point at this account" is a real error.
            if ((string) $exception->getCode() !== '23000') {
                throw $exception;
            }

            DB::transaction(fn () => $this->close($user));
            $this->deleteFiles($personalFiles);

            return;
        }

        $this->deleteFiles($personalFiles);
        $this->deleteFiles($designFiles);
    }

    /** Wipes the person from an account that has to stay as a row. */
    private function close(User $user): void
    {
        $profile = $user->designerProfile;
        $provider = $user->printProvider;

        // Designs stop selling and leave every basket; past orders keep them.
        $designIds = Design::where('designer_id', $user->id)->pluck('id');
        CartItem::whereIn('design_id', $designIds)->delete();
        Design::where('designer_id', $user->id)->whereIn('status', ['published', 'review'])->update(['status' => 'draft', 'published_at' => null]);

        if ($profile) {
            $profile->forceFill(['full_name' => 'حساب محذوف', 'bio' => null, 'skills' => null, 'portfolio_url' => null, 'profile_image' => null])->save();
        }

        if ($provider) {
            $provider->branches()->update(['is_active' => false]);
            $provider->forceFill([
                'company_name' => 'مطبعة محذوفة', 'phone' => null, 'whatsapp_number' => null, 'address' => null,
                'verification_document' => null, 'is_active' => false,
            ] + (DB::getSchemaBuilder()->hasColumn('print_providers', 'id_document') ? ['id_document' => null] : [])
              + (DB::getSchemaBuilder()->hasColumn('print_providers', 'contact_email') ? ['contact_email' => null] : []))->save();
        }

        SocialAccount::where('user_id', $user->id)->delete();
        Notification::where('user_id', $user->id)->delete();
        DesignFavorite::where('user_id', $user->id)->delete();
        DB::table('sessions')->where('user_id', $user->id)->delete();

        // Saved addresses go too, unless an order still refers to one (the order keeps its own copy of the details).
        Address::where('user_id', $user->id)->get()->each(function (Address $address) {
            try {
                DB::transaction(fn () => $address->delete());
            } catch (QueryException) {
                // Kept: an order points at it.
            }
        });

        $user->forceFill([
            'name' => 'حساب محذوف',
            'email' => 'deleted-'.$user->id.'-'.Str::lower(Str::random(8)).'@deleted.invalid',
            'phone' => null,
            'avatar_path' => null,
            'email_verified_at' => null,
            'is_active' => false,
            'password' => Str::random(40),
            'remember_token' => null,
        ])->save();
    }

    /** Pictures and identity files of the person, on either disk (documents moved from public to private over time). */
    private function personalFiles(User $user): array
    {
        $provider = $user->printProvider;

        return array_filter([
            $user->avatar_path,
            $user->designerProfile?->profile_image,
            $provider?->verification_document,
            $provider?->getAttribute('id_document'),
        ]);
    }

    /** The designer's design pictures and private artwork (removed only when the designs themselves are removed). */
    private function designFiles(User $user): array
    {
        $images = Design::where('designer_id', $user->id)->pluck('image')
            ->filter(fn ($image) => str_starts_with((string) $image, 'storage/'))
            ->map(fn ($image) => Str::after($image, 'storage/'))->all();

        return array_merge($images, ['dir:designer-designs/'.$user->id]);
    }

    private function deleteFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (str_starts_with($path, 'dir:')) {
                Storage::disk('local')->deleteDirectory(substr($path, 4));

                continue;
            }

            Storage::disk('public')->delete($path);
            Storage::disk('local')->delete($path);
        }
    }
}
