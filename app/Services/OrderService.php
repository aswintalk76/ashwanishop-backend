<?php

namespace App\Services;

use App\Events\OrderCompleted;
use App\Events\PaymentVerified;
use App\Jobs\SendOrderConfirmationEmail;
use App\Jobs\SendThankYouEmail;
use App\Models\Cart;
use App\Models\DeliveryLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private QrService $qrService,
        private WhatsAppService $whatsAppService,
        private CartService $cartService,
    ) {}

    public function createFromCart(User $user, array $data, ?string $guestToken = null): Order
    {
        $this->cartService->mergeGuestCart($user, $guestToken);

        return DB::transaction(function () use ($user, $data) {
            $cartItems = $this->cartService->getActiveCartItems($user);

            if ($cartItems->isEmpty()) {
                throw new \InvalidArgumentException('Cart is empty');
            }

            $subtotal = 0;
            foreach ($cartItems as $item) {
                if ($item->product->stock < $item->quantity) {
                    throw new \InvalidArgumentException("Insufficient stock for {$item->product->name}");
                }
                $subtotal += $item->product->final_price * $item->quantity;
            }

            $shipping = (float) ($data['shipping'] ?? 0);
            $discount = (float) ($data['discount'] ?? 0);
            $total = $subtotal - $discount + $shipping;

            $order = Order::create([
                'order_number' => 'ASH-'.strtoupper(Str::random(8)),
                'user_id' => $user->id,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping' => $shipping,
                'total' => $total,
                'coupon_code' => $data['coupon_code'] ?? null,
                'shipping_address' => $data['shipping_address'],
                'shipping_city' => $data['shipping_city'],
                'shipping_state' => $data['shipping_state'],
                'shipping_pincode' => $data['shipping_pincode'],
                'shipping_phone' => $data['shipping_phone'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_sku' => $item->product->sku,
                    'price' => $item->product->final_price,
                    'quantity' => $item->quantity,
                    'total' => $item->product->final_price * $item->quantity,
                ]);

                Product::where('id', $item->product_id)
                    ->decrement('stock', $item->quantity);
            }

            Payment::create(['order_id' => $order->id, 'status' => 'pending']);

            Cart::where('user_id', $user->id)->where('save_for_later', false)->delete();

            return $order->load(['items', 'payment', 'user']);
        });
    }

    public function submitPaymentProof(Order $order, array $data): Payment
    {
        $payment = $order->payment;
        $payment->update([
            'transaction_id' => $data['transaction_id'],
            'screenshot' => $data['screenshot'] ?? $payment->screenshot,
            'status' => 'pending',
        ]);

        return $payment->fresh();
    }

    public function verifyPayment(Order $order, int $adminId): Order
    {
        return DB::transaction(function () use ($order, $adminId) {
            $order->payment->update([
                'status' => 'verified',
                'verified_by' => $adminId,
                'verified_at' => now(),
            ]);

            $order->update(['status' => 'payment_verified']);

            $qrToken = $this->qrService->generateDeliveryToken($order);
            $plainToken = $qrToken->plain_token ?? null;

            SendOrderConfirmationEmail::dispatch($order, $plainToken);
            PaymentVerified::dispatch($order);
            $this->whatsAppService->notifyAdminPaymentVerified($order);

            return $order->fresh(['items', 'payment', 'user']);
        });
    }

    public function markDelivered(Order $order, int $adminId): Order
    {
        return DB::transaction(function () use ($order, $adminId) {
            $order->update(['status' => 'delivered']);

            DeliveryLog::create([
                'order_id' => $order->id,
                'admin_id' => $adminId,
                'action' => 'delivered',
                'metadata' => ['scanned_at' => now()->toIso8601String()],
            ]);

            return $order;
        });
    }

    public function completeOrder(Order $order): Order
    {
        $order->update(['status' => 'completed']);
        OrderCompleted::dispatch($order);
        SendThankYouEmail::dispatch($order);

        return $order;
    }
}
