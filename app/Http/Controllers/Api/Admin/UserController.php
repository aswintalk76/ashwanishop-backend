<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::role('user')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($users);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['user' => $user->load('roles', 'orders')]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'is_active' => 'boolean',
            'phone' => 'nullable|string',
        ]);

        $user->update($data);

        return response()->json(['user' => $user]);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->isAdmin()) {
            return response()->json(['message' => 'Cannot delete admin'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted']);
    }
}
