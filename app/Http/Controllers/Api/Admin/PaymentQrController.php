<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentQrController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'payment_qr_image' => Setting::get('payment_qr_image'),
            'upi_id' => Setting::get('upi_id'),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'upi_id' => 'nullable|string',
            'payment_qr' => 'nullable|image|max:2048',
        ]);

        if ($request->filled('upi_id')) {
            Setting::set('upi_id', $request->upi_id, 'payment');
        }

        if ($request->hasFile('payment_qr')) {
            $path = $request->file('payment_qr')->store('payment', 'public');
            Setting::set('payment_qr_image', $path, 'payment');
        }

        return response()->json([
            'payment_qr_image' => Setting::get('payment_qr_image'),
            'upi_id' => Setting::get('upi_id'),
            'message' => 'Payment QR updated',
        ]);
    }
}
