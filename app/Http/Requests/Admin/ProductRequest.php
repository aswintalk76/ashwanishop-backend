<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'sku' => 'required|string|unique:products,sku,'.$this->product?->id,
            'stock' => 'required|integer|min:0',
            'status' => 'in:active,inactive,draft',
            'images' => 'nullable|array',
            'images.*' => 'image|max:5120',
        ];
    }
}
