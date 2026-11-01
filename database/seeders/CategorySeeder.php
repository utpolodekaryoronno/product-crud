<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * @var array<int, array{name: string, description: string}>
     */
    private const CATEGORIES = [
        ['name' => 'Lighting', 'description' => 'Lamps and fixtures that shape the mood of a room.'],
        ['name' => 'Kitchen & Dining', 'description' => 'Tools and tableware for everyday cooking and sharing.'],
        ['name' => 'Textiles', 'description' => 'Throws, towels and woven goods in natural fibres.'],
        ['name' => 'Audio', 'description' => 'Speakers and sound equipment for the home.'],
        ['name' => 'Bags & Travel', 'description' => 'Carry essentials for daily commutes and weekends away.'],
        ['name' => 'Desk & Stationery', 'description' => 'Paper, notebooks and tools for focused work.'],
        ['name' => 'Home Decor', 'description' => 'Finishing touches that make a space feel lived in.'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            Category::query()->updateOrCreate(
                ['category_slug' => Str::slug($category['name'])],
                [
                    'category_name' => $category['name'],
                    'description' => $category['description'],
                    'is_active' => true,
                ],
            );
        }
    }
}
