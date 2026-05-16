<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CartService
{
    /**
     * Merge guest cart items into the authenticated user's cart.
     */
    public function mergeGuestCart(User $user, ?string $guestToken): void
    {
        if (! $guestToken) {
            return;
        }

        DB::transaction(function () use ($user, $guestToken) {
            $guestItems = Cart::where('guest_token', $guestToken)
                ->whereNull('user_id')
                ->get();

            foreach ($guestItems as $guestItem) {
                $userCart = Cart::where('user_id', $user->id)
                    ->where('product_id', $guestItem->product_id)
                    ->first();

                if ($userCart) {
                    $userCart->update([
                        'quantity' => $userCart->quantity + $guestItem->quantity,
                        'guest_token' => null,
                    ]);
                    $guestItem->delete();
                } else {
                    $guestItem->update([
                        'user_id' => $user->id,
                        'guest_token' => null,
                    ]);
                }
            }
        });
    }

    public function getActiveCartItems(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return Cart::with('product')
            ->where('user_id', $user->id)
            ->where('save_for_later', false)
            ->get();
    }
}
