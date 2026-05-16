<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'price', 'discount',
        'sku', 'stock', 'rating', 'review_count', 'images', 'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount' => 'decimal:2',
            'rating' => 'decimal:2',
            'images' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function getFinalPriceAttribute(): float
    {
        if ($this->discount > 0) {
            return round($this->price - ($this->price * $this->discount / 100), 2);
        }

        return (float) $this->price;
    }

    public function getPrimaryImageAttribute(): ?string
    {
        $images = $this->images ?? [];

        return $images[0] ?? null;
    }
}
