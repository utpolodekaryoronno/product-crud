<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'product_name',
    'product_slug',
    'description',
    'price',
    'stock',
    'product_thumbnail',
    'brand_id',
    'category_id',
    'is_active',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasUniqueSlug;

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

    protected static function slugColumn(): string
    {
        return 'product_slug';
    }

    protected static function slugSourceColumn(): string
    {
        return 'product_name';
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
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
     * Scope a query to products that belong to the given brand slug.
     */
    public function scopeForBrand(Builder $query, ?string $brandSlug): Builder
    {
        return $query->when($brandSlug, fn (Builder $query, string $slug) => $query
            ->whereHas('brand', fn (Builder $query) => $query->where('brand_slug', $slug)));
    }

    /**
     * Scope a query to products that belong to the given category slug.
     */
    public function scopeForCategory(Builder $query, ?string $categorySlug): Builder
    {
        return $query->when($categorySlug, fn (Builder $query, string $slug) => $query
            ->whereHas('category', fn (Builder $query) => $query->where('category_slug', $slug)));
    }

    /**
     * Scope a query to products that carry the given tag slug.
     */
    public function scopeForTag(Builder $query, ?string $tagSlug): Builder
    {
        return $query->when($tagSlug, fn (Builder $query, string $slug) => $query
            ->whereHas('tags', fn (Builder $query) => $query->where('tag_slug', $slug)));
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
