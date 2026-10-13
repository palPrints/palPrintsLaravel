<?php

namespace App\Support;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\PrintFile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A visitor who is not signed in can still fill a cart. Their cart belongs to a hidden "guest" user that has no
 * role and a made-up address, so all the existing cart code (shop routing, paper files, designs) works unchanged.
 * The visitor is never logged in as that user: its id only lives in the session, and the ShopperAccess middleware
 * lends it to the request on shop pages. When the visitor signs in as a customer, MergeGuestCart moves the cart over.
 */
class GuestShopper
{
    public const SESSION_KEY = 'guest_shopper_id';

    public const EMAIL_DOMAIN = '@guest.palprints.invalid';

    /** The guest user remembered in this session, if it still exists. */
    public static function fromSession(Request $request): ?User
    {
        $id = $request->session()->get(self::SESSION_KEY);
        $user = $id ? User::find($id) : null;

        if ($user && ! $user->isGuestShopper()) {
            return null;
        }

        return $user;
    }

    public static function create(Request $request): User
    {
        $user = User::create([
            'name' => 'زائر',
            'email' => 'guest-'.Str::uuid().self::EMAIL_DOMAIN,
            'password' => Hash::make(Str::random(48)),
            'is_active' => true,
        ]);

        $request->session()->put(self::SESSION_KEY, $user->id);

        return $user;
    }

    /** Moves what the guest put in the cart (lines and uploaded files) into the customer's own cart. */
    public static function merge(User $guest, User $customer): void
    {
        DB::transaction(function () use ($guest, $customer) {
            $guestCarts = Cart::where('user_id', $guest->id)->where('status', 'active')->get();

            if ($guestCarts->isNotEmpty()) {
                $target = Cart::firstOrCreate(['user_id' => $customer->id, 'status' => 'active']);

                foreach ($guestCarts as $guestCart) {
                    foreach ($guestCart->items()->get() as $item) {
                        self::moveItem($item, $target);
                    }
                }
            }

            // Files follow their owner, so the cart can still show and send them.
            PrintFile::where('user_id', $guest->id)->update(['user_id' => $customer->id]);
        });

        self::discard($guest);
    }

    /** Same product, variant and design in both carts: add the quantities instead of listing the line twice. */
    private static function moveItem(CartItem $item, Cart $target): void
    {
        $same = $item->item_type === CartItem::TYPE_CATALOG_DESIGN
            ? CartItem::where([
                'cart_id' => $target->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'design_id' => $item->design_id,
                'item_type' => CartItem::TYPE_CATALOG_DESIGN,
            ])->first()
            : null;

        if ($same && ($same->selected_options ?? []) == ($item->selected_options ?? [])) {
            $same->update(['quantity' => min(99, $same->quantity + $item->quantity)]);
            $item->delete();

            return;
        }

        $item->update(['cart_id' => $target->id]);
    }

    /** Deletes a guest user and its leftovers (cart rows go with it; uploaded files are removed from disk). */
    public static function discard(User $guest): void
    {
        if (! $guest->isGuestShopper()) {
            return;
        }

        foreach (['customer-print-files/tmp/', 'customer-print-files/previews/'] as $folder) {
            // Files moved to a customer keep their original path, so keep the folder while someone else still owns files in it.
            $usedByOthers = PrintFile::where('user_id', '!=', $guest->id)
                ->where(fn ($query) => $query->where('stored_path', 'like', $folder.$guest->id.'/%')
                    ->orWhere('preview_path', 'like', $folder.$guest->id.'/%'))
                ->exists();
            if (! $usedByOthers) {
                Storage::disk('local')->deleteDirectory($folder.$guest->id);
            }
        }

        $guest->delete();
    }

    /** Removes guests that were abandoned (no activity for $days days). Returns how many were removed. */
    public static function prune(int $days = 7): int
    {
        $count = 0;

        User::where('email', 'like', '%'.self::EMAIL_DOMAIN)
            ->where('updated_at', '<', now()->subDays($days))
            ->each(function (User $guest) use (&$count) {
                self::discard($guest);
                $count++;
            });

        return $count;
    }
}
