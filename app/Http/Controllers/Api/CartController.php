<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function __construct(private CartService $cartService) {}
    private function guestToken(Request $request): ?string
    {
        return $request->header('X-Guest-Token') ?? $request->input('guest_token');
    }

    private function resolveGuestToken(Request $request): string
    {
        return $this->guestToken($request) ?? Str::random(32);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Cart::with('product.category');

        if ($request->user()) {
            $guestToken = $this->guestToken($request);
            if ($guestToken) {
                $this->cartService->mergeGuestCart($request->user(), $guestToken);
            }
            $query->where('user_id', $request->user()->id);
        } else {
            $token = $this->guestToken($request);
            if (! $token) {
                return response()->json(['items' => [], 'guest_token' => null]);
            }
            $query->where('guest_token', $token);
        }

        $items = $query->where('save_for_later', false)->get();

        return response()->json([
            'items' => $items,
            'guest_token' => $request->user() ? null : $this->guestToken($request),
            'subtotal' => $items->sum(fn ($i) => $i->product->final_price * $i->quantity),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'integer|min:1|max:99',
        ]);

        $product = Product::findOrFail($request->product_id);
        $quantity = $request->integer('quantity', 1);

        $data = ['product_id' => $product->id, 'quantity' => $quantity];

        if ($request->user()) {
            $cart = Cart::updateOrCreate(
                ['user_id' => $request->user()->id, 'product_id' => $product->id],
                ['quantity' => $quantity, 'guest_token' => null]
            );
        } else {
            $token = $this->resolveGuestToken($request);
            $cart = Cart::updateOrCreate(
                ['guest_token' => $token, 'product_id' => $product->id],
                ['quantity' => $quantity, 'user_id' => null]
            );

            return response()->json([
                'item' => $cart->load('product'),
                'guest_token' => $token,
            ], 201);
        }

        return response()->json(['item' => $cart->load('product')], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate(['quantity' => 'required|integer|min:1|max:99']);

        $cart = $this->findCartItem($request, $id);
        $cart->update(['quantity' => $request->quantity]);

        return response()->json(['item' => $cart->load('product')]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->findCartItem($request, $id)->delete();

        return response()->json(['message' => 'Removed from cart']);
    }

    public function clear(Request $request): JsonResponse
    {
        $query = Cart::query();
        if ($request->user()) {
            $query->where('user_id', $request->user()->id);
        } else {
            $query->where('guest_token', $this->guestToken($request));
        }
        $query->delete();

        return response()->json(['message' => 'Cart cleared']);
    }

    private function findCartItem(Request $request, int $id): Cart
    {
        $query = Cart::where('id', $id);

        if ($request->user()) {
            $query->where('user_id', $request->user()->id);
        } else {
            $query->where('guest_token', $this->guestToken($request));
        }

        return $query->firstOrFail();
    }
}
