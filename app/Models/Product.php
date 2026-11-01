<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'product_name',
    'product_slug',
    'description',
    'price',
    'stock',
    'product_thumbnail',
    'is_active',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Resolve public product routes using the slug instead of the primary key.
     */
    public function getRouteKeyName(): string
    {
        return 'product_slug';
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            if (blank($product->product_slug)) {
                $product->product_slug = static::generateUniqueSlug($product->product_name, $product->getKey());
            }
        });
    }

    /**
     * Build a slug that does not collide with another product.
     */
    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'product';
        $slug = $baseSlug;
        $suffix = 2;

        while (static::query()
            ->where('product_slug', $slug)
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * Scope a query to only include products visible in the storefront.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to search products by name or description.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $term = '%'.addcslashes($term, '%_\\').'%';

        return $query->where(function (Builder $query) use ($term): void {
            $query->where('product_name', 'like', $term)
                ->orWhere('description', 'like', $term);
        });
    }

    /**
     * Get the public URL for the product thumbnail, falling back to a placeholder.
     */
    public function getThumbnailUrlAttribute(): string
    {
        if (blank($this->product_thumbnail)) {
            return asset('images/product-placeholder.svg');
        }

        if (! Storage::disk('public')->exists($this->product_thumbnail)) {
            return asset('images/product-placeholder.svg');
        }

        return Storage::disk('public')->url($this->product_thumbnail);
    }

    /**
     * Determine whether the product is currently sold out.
     */
    public function getIsOutOfStockAttribute(): bool
    {
        return $this->stock <= 0;
    }

    /**
     * Get a human friendly stock label.
     */
    public function getStockLabelAttribute(): string
    {
        return match (true) {
            $this->stock <= 0 => 'Out of stock',
            $this->stock <= 5 => 'Low stock',
            default => 'In stock',
        };
    }
}
