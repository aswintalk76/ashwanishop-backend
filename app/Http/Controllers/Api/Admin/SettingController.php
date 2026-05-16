<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['settings' => Setting::all()->groupBy('group')]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
            'settings.*.group' => 'nullable|string',
        ]);

        foreach ($data['settings'] as $item) {
            Setting::set($item['key'], $item['value'] ?? '', $item['group'] ?? 'general');
        }

        return response()->json(['message' => 'Settings updated']);
    }
}
