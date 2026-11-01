<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait HasUniqueSlug
{
    /**
     * The column that stores the slug.
     */
    protected static function slugColumn(): string
    {
        return 'slug';
    }

    /**
     * The column the slug is generated from.
     */
    protected static function slugSourceColumn(): string
    {
        return 'name';
    }

    protected static function bootHasUniqueSlug(): void
    {
        static::saving(function ($model): void {
            $slugColumn = $model::slugColumn();

            if (blank($model->getAttribute($slugColumn))) {
                $model->setAttribute(
                    $slugColumn,
                    $model::generateUniqueSlug(
                        (string) $model->getAttribute($model::slugSourceColumn()),
                        $model->getKey(),
                    ),
                );
            }
        });
    }

    /**
     * Build a slug that does not collide with another record.
     */
    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $slugColumn = static::slugColumn();
        $baseSlug = Str::slug($name) ?: 'item';
        $slug = $baseSlug;
        $suffix = 2;

        while (static::query()
            ->where($slugColumn, $slug)
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
