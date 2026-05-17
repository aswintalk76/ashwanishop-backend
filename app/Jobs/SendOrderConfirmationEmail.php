<?php

namespace App\Jobs;

use App\Mail\OrderConfirmationMail;
use App\Models\EmailLog;
use App\Models\Order;
use App\Services\QrService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public ?string $plainDeliveryToken = null,
    ) {}

    public function handle(QrService $qrService): void
    {
        $order = $this->order->load(['items', 'user']);
        $qrBase64 = null;

        if ($this->plainDeliveryToken) {
            try {
                $payload = $qrService->getDeliveryQrPayload($this->plainDeliveryToken, $order->order_number);
                $qrBase64 = $qrService->generateQrImage($payload);
            } catch (\Throwable) {
                // GD may be disabled locally; customer still sees QR on the order page (client-rendered).
                $qrBase64 = null;
            }
        }

        $log = EmailLog::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'type' => 'order_confirmation',
            'recipient' => $order->user->email,
            'subject' => "Order Confirmed - {$order->order_number}",
            'status' => 'queued',
        ]);

        try {
            Mail::to($order->user->email)->send(new OrderConfirmationMail($order, $qrBase64, $this->plainDeliveryToken));
            $log->update(['status' => 'sent']);
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error' => $e->getMessage()]);
        }
    }
}
