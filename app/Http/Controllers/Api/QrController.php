<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QrToken;
use App\Services\OrderService;
use App\Services\QrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrController extends Controller
{
    public function __construct(
        private QrService $qrService,
        private OrderService $orderService,
    ) {}

    public function scanDelivery(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'order_number' => 'required|string',
        ]);

        $qrToken = $this->qrService->verifyToken($request->token);

        if (! $qrToken) {
            return response()->json(['message' => 'Invalid or expired QR token'], 422);
        }

        $order = $qrToken->order()->with(['items', 'user'])->first();

        if ($order->order_number !== $request->order_number) {
            return response()->json(['message' => 'Order mismatch'], 422);
        }

        if (in_array($order->status, ['delivered', 'completed', 'cancelled'])) {
            return response()->json([
                'message' => 'Order already delivered or completed',
                'order' => $order,
            ], 422);
        }

        if ($order->status !== 'shipped') {
            $hint = match ($order->status) {
                'payment_verified', 'processing' => 'Update order status to Shipped before scanning delivery QR.',
                'pending' => 'Verify payment and ship the order before delivery scan.',
                default => 'Order is not ready for delivery scan.',
            };

            return response()->json([
                'message' => $hint,
                'order' => $order,
                'required_status' => 'shipped',
            ], 422);
        }

        $qrToken->update(['used_at' => now()]);
        $order = $this->orderService->markDelivered($order, $request->user()->id);
        $order = $this->orderService->completeOrder($order->fresh());

        return response()->json([
            'message' => 'Delivery verified. Order marked delivered and completed.',
            'order' => $order->load(['items', 'user']),
        ]);
    }
}
