<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Requests\Order\PaymentProofRequest;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\QrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private QrService $qrService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $orders = Order::with(['items', 'payment'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($orders);
    }

    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::with(['items.product', 'payment'])
            ->where('user_id', $request->user()->id)
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $deliveryQr = $this->qrService->getDeliveryQrForCustomer($order);

        return response()->json([
            'order' => $order,
            'delivery_qr' => $deliveryQr,
        ]);
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createFromCart(
                $request->user(),
                $request->validated(),
                $request->header('X-Guest-Token')
            );

            return response()->json([
                'order' => $order,
                'message' => 'Order placed successfully. Complete payment to proceed.',
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function submitPaymentProof(PaymentProofRequest $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('user_id', $request->user()->id)
            ->where('order_number', $orderNumber)
            ->where('status', 'pending')
            ->firstOrFail();

        $screenshot = null;
        if ($request->hasFile('screenshot')) {
            $screenshot = $request->file('screenshot')->store('payments', 'public');
        }

        $payment = $this->orderService->submitPaymentProof($order, [
            'transaction_id' => $request->transaction_id,
            'screenshot' => $screenshot,
        ]);

        return response()->json([
            'payment' => $payment,
            'message' => 'Payment proof submitted. Admin will verify shortly.',
        ]);
    }
}
