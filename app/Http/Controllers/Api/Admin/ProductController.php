<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Product::with('category')->orderByDesc('created_at')->paginate(20)
        );
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']).'-'.Str::random(4);

        if ($request->hasFile('images')) {
            $paths = [];
            foreach ($request->file('images') as $image) {
                $paths[] = $image->store('products', 'public');
            }
            $data['images'] = $paths;
        }

        $product = Product::create($data);

        return response()->json(['product' => $product], 201);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json(['product' => $product->load('category')]);
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('images')) {
            $paths = $product->images ?? [];
            foreach ($request->file('images') as $image) {
                $paths[] = $image->store('products', 'public');
            }
            $data['images'] = $paths;
        }

        $product->update($data);

        return response()->json(['product' => $product->fresh()]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted']);
    }
}
