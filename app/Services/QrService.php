<?php

namespace App\Services;

use App\Models\Order;
use App\Models\QrToken;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Str;

class QrService
{
    public function generateDeliveryToken(Order $order): QrToken
    {
        $token = Str::random(64);
        $hours = (int) config('app.qr_token_expiry_hours', env('QR_TOKEN_EXPIRY_HOURS', 72));

        $qrToken = QrToken::create([
            'order_id' => $order->id,
            'token' => hash('sha256', $token),
            'type' => 'delivery',
            'expires_at' => now()->addHours($hours),
        ]);

        $order->update([
            'delivery_token' => $qrToken->token,
            'delivery_token_expires_at' => $qrToken->expires_at,
            'encrypted_delivery_token' => encrypt($token),
        ]);

        $qrToken->plain_token = $token;

        return $qrToken;
    }

    public function getDeliveryQrForCustomer(Order $order): ?array
    {
        if ($order->payment?->status !== 'verified') {
            return null;
        }

        if (in_array($order->status, ['delivered', 'completed', 'cancelled'])) {
            return null;
        }

        $activeToken = $order->qrTokens()
            ->where('type', 'delivery')
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $activeToken) {
            return null;
        }

        $plainToken = null;
        if ($order->encrypted_delivery_token) {
            try {
                $plainToken = decrypt($order->encrypted_delivery_token);
            } catch (\Throwable) {
                $plainToken = null;
            }
        }

        if (! $plainToken) {
            $order->qrTokens()
                ->where('type', 'delivery')
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            $qrToken = $this->generateDeliveryToken($order->fresh());
            $plainToken = $qrToken->plain_token;
            $order = $order->fresh();
        }

        $payload = $this->getDeliveryQrPayload($plainToken, $order->order_number);

        return [
            'order_number' => $order->order_number,
            'token' => $plainToken,
            'qr_payload' => $payload,
            'expires_at' => $order->delivery_token_expires_at?->toIso8601String(),
        ];
    }

    public function generateQrImage(string $data): string
    {
        $builder = new Builder(
            writer: new PngWriter(),
            data: $data,
            size: 300,
            margin: 10,
        );

        $result = $builder->build();

        return base64_encode($result->getString());
    }

    public function getDeliveryQrPayload(string $plainToken, string $orderNumber): string
    {
        return json_encode([
            'type' => 'delivery',
            'order_number' => $orderNumber,
            'token' => $plainToken,
        ]);
    }

    public function verifyToken(string $plainToken): ?QrToken
    {
        $hashed = hash('sha256', $plainToken);

        return QrToken::where('token', $hashed)
            ->where('type', 'delivery')
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
    }
}
