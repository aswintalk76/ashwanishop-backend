<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['user', 'payment', 'items']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%"));
            });
        }

        return response()->json($query->orderByDesc('created_at')->paginate(15));
    }

    public function show(string $orderNumber): JsonResponse
    {
        $order = Order::with(['user', 'items.product', 'payment.verifier'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        return response()->json(['order' => $order]);
    }

    public function updateStatus(Request $request, string $orderNumber): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,payment_verified,processing,shipped,delivered,completed,cancelled',
        ]);

        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $newStatus = $request->status;

        if ($newStatus === 'completed' && $order->status !== 'delivered') {
            return response()->json([
                'message' => 'Order must be delivered before marking as completed.',
            ], 422);
        }

        if ($newStatus === 'delivered' && ! in_array($order->status, ['shipped', 'delivered'])) {
            return response()->json([
                'message' => 'Order must be shipped before marking as delivered.',
            ], 422);
        }

        $previousStatus = $order->status;
        $order->update(['status' => $newStatus]);

        if ($newStatus === 'delivered' && $previousStatus !== 'delivered') {
            $order = $this->orderService->completeOrder($order->fresh());
        }

        return response()->json(['order' => $order->fresh(['items', 'payment', 'user']), 'message' => 'Status updated']);
    }

    public function verifyPayment(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::with('payment')
            ->where('order_number', $orderNumber)
            ->where('status', 'pending')
            ->firstOrFail();

        $order = $this->orderService->verifyPayment($order, $request->user()->id);

        return response()->json([
            'order' => $order,
            'message' => 'Payment verified. Customer notified via email & WhatsApp.',
        ]);
    }
}
