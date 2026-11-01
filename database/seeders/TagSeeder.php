<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private const TAGS = [
        'Bestseller',
        'New Arrival',
        'Handmade',
        'Eco Friendly',
        'Limited Edition',
        'Gift Idea',
    ];

    public function run(): void
    {
        foreach (self::TAGS as $tag) {
            Tag::query()->updateOrCreate(
                ['tag_slug' => Str::slug($tag)],
                ['tag_name' => $tag],
            );
        }
    }
}
