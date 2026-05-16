<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function public(): JsonResponse
    {
        $keys = [
            'site_name', 'site_tagline', 'contact_email', 'contact_phone',
            'payment_qr_image', 'shipping_policy', 'about_text',
        ];

        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = Setting::get($key);
        }

        return response()->json(['settings' => $settings]);
    }
}
