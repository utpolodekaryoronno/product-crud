<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['brand_name', 'brand_slug', 'description', 'brand_logo', 'is_active'])]
class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
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
        return 'brand_slug';
    }

    protected static function slugColumn(): string
    {
        return 'brand_slug';
    }

    protected static function slugSourceColumn(): string
    {
        return 'brand_name';
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Scope a query to only include brands visible in the storefront.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the public URL for the brand logo.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (blank($this->brand_logo) || ! Storage::disk('public')->exists($this->brand_logo)) {
            return null;
        }

        return Storage::disk('public')->url($this->brand_logo);
    }
}
