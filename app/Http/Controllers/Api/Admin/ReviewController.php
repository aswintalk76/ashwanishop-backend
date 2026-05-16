<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(): JsonResponse
    {
        $reviews = Review::with(['user', 'product'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($reviews);
    }

    public function update(Request $request, Review $review): JsonResponse
    {
        $request->validate(['status' => 'required|in:approved,rejected']);

        $review->update(['status' => $request->status]);

        if ($request->status === 'approved') {
            $product = $review->product;
            $avg = Review::where('product_id', $product->id)->where('status', 'approved')->avg('rating');
            $count = Review::where('product_id', $product->id)->where('status', 'approved')->count();
            $product->update(['rating' => round($avg, 2), 'review_count' => $count]);
        }

        return response()->json(['review' => $review]);
    }
}
