<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = Wishlist::with('product.category')
            ->where('user_id', $request->user()->id)
            ->get();

        return response()->json(['items' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['product_id' => 'required|exists:products,id']);

        $item = Wishlist::firstOrCreate([
            'user_id' => $request->user()->id,
            'product_id' => $request->product_id,
        ]);

        return response()->json(['item' => $item->load('product')], 201);
    }

    public function destroy(Request $request, int $productId): JsonResponse
    {
        Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $productId)
            ->delete();

        return response()->json(['message' => 'Removed from wishlist']);
    }
}
