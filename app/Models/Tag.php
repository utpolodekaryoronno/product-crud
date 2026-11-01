<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['tag_name', 'tag_slug'])]
class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory, HasUniqueSlug;

    public function getRouteKeyName(): string
    {
        return 'tag_slug';
    }

    protected static function slugColumn(): string
    {
        return 'tag_slug';
    }

    protected static function slugSourceColumn(): string
    {
        return 'tag_name';
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
