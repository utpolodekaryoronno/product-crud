<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    /**
     * @var array<int, array{name: string, description: string}>
     */
    private const BRANDS = [
        ['name' => 'Lumen Home', 'description' => 'Warm, considered lighting and objects for the home.'],
        ['name' => 'Kanso Studio', 'description' => 'Simple, functional pieces made with honest materials.'],
        ['name' => 'Alpine Works', 'description' => 'Outdoor-ready gear and audio built for everyday adventures.'],
        ['name' => 'Grove & Grain', 'description' => 'Natural wood and canvas goods crafted in small batches.'],
        ['name' => 'Ember Supply', 'description' => 'Coffee, candles and the small rituals that start a day.'],
        ['name' => 'Loom & Thread', 'description' => 'Soft, durable textiles woven from natural fibres.'],
    ];

    public function run(): void
    {
        foreach (self::BRANDS as $brand) {
            Brand::query()->updateOrCreate(
                ['brand_slug' => Str::slug($brand['name'])],
                [
                    'brand_name' => $brand['name'],
                    'description' => $brand['description'],
                    'is_active' => true,
                ],
            );
        }
    }
}
