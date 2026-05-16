<?php

namespace App\Services;

use App\Models\Order;
use App\Models\WhatsappLog;
use Twilio\Rest\Client;

class WhatsAppService
{
    public function notifyAdminPaymentVerified(Order $order): void
    {
        $order->load(['user', 'payment']);
        $adminNumber = config('services.whatsapp.admin_number', env('ADMIN_WHATSAPP_NUMBER'));

        $message = "🛒 *New Payment Verified*\n\n"
            ."Order: *{$order->order_number}*\n"
            ."Customer: {$order->user->name}\n"
            ."Amount: ₹{$order->total}\n"
            ."Txn ID: {$order->payment?->transaction_id}\n"
            ."Address: {$order->shipping_address}, {$order->shipping_city} - {$order->shipping_pincode}";

        $this->send($adminNumber, $message, $order->id);
    }

    public function send(string $to, string $message, ?int $orderId = null): void
    {
        $log = WhatsappLog::create([
            'order_id' => $orderId,
            'recipient' => $to,
            'message' => $message,
            'status' => 'queued',
        ]);

        try {
            if (config('services.whatsapp.provider') === 'twilio') {
                $sid = config('services.whatsapp.twilio_sid');
                $token = config('services.whatsapp.twilio_token');
                $from = config('services.whatsapp.twilio_from');

                if ($sid && $token && $from) {
                    $client = new Client($sid, $token);
                    $response = $client->messages->create($to, [
                        'from' => $from,
                        'body' => $message,
                    ]);
                    $log->update(['status' => 'sent', 'response' => $response->sid]);
                } else {
                    $log->update(['status' => 'sent', 'response' => 'simulated - configure Twilio']);
                }
            }
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'response' => $e->getMessage()]);
        }
    }
}
