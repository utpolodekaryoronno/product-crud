<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['category_name', 'category_slug', 'description', 'category_image', 'is_active'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasUniqueSlug;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'category_slug';
    }

    protected static function slugColumn(): string
    {
        return 'category_slug';
    }

    protected static function slugSourceColumn(): string
    {
        return 'category_name';
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Scope a query to only include categories visible in the storefront.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the public URL for the category image.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (blank($this->category_image) || ! Storage::disk('public')->exists($this->category_image)) {
            return null;
        }

        return Storage::disk('public')->url($this->category_image);
    }
}
