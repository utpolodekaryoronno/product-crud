<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Curated demo catalogue used by the storefront and the admin panel.
     *
     * @var array<int, array{name: string, description: string, price: float, stock: int, active: bool, colors: array{0: string, 1: string}}>
     */
    private const PRODUCTS = [
        [
            'name' => 'Aurora Desk Lamp',
            'description' => 'A sculptural desk lamp with a warm dimmable glow. The brushed aluminium arm rotates a full 180 degrees, so you can point light exactly where the work happens.',
            'price' => 128.00,
            'stock' => 24,
            'active' => true,
            'colors' => ['#f59e0b', '#b45309'],
        ],
        [
            'name' => 'Kanso Ceramic Mug',
            'description' => 'Hand-thrown stoneware with a soft matte glaze. Holds 350 ml and keeps drinks warm thanks to the generous wall thickness.',
            'price' => 24.50,
            'stock' => 80,
            'active' => true,
            'colors' => ['#38bdf8', '#0369a1'],
        ],
        [
            'name' => 'Terra Linen Throw',
            'description' => 'Stonewashed European linen woven in a subtle herringbone. Light enough for summer evenings, cosy enough for winter mornings.',
            'price' => 89.00,
            'stock' => 18,
            'active' => true,
            'colors' => ['#fb7185', '#9f1239'],
        ],
        [
            'name' => 'Nimbus Wireless Speaker',
            'description' => 'Room-filling 360 degree sound in a compact recycled-aluminium body. Twenty hours of battery life and a single-cable USB-C charge.',
            'price' => 149.99,
            'stock' => 35,
            'active' => true,
            'colors' => ['#a78bfa', '#5b21b6'],
        ],
        [
            'name' => 'Alpine Daypack',
            'description' => 'A weatherproof 22 litre daypack with a padded laptop sleeve, quick-access top pocket and breathable back panel.',
            'price' => 74.00,
            'stock' => 42,
            'active' => true,
            'colors' => ['#34d399', '#065f46'],
        ],
        [
            'name' => 'Grove Cutting Board',
            'description' => 'End-grain acacia with a juice groove and a finger notch for easy lifting. Finished with food-safe mineral oil.',
            'price' => 42.00,
            'stock' => 60,
            'active' => true,
            'colors' => ['#fbbf24', '#92400e'],
        ],
        [
            'name' => 'Halo Wall Clock',
            'description' => 'A quiet sweep movement behind a slim oak frame. Reads clearly from across the room without shouting for attention.',
            'price' => 58.00,
            'stock' => 12,
            'active' => true,
            'colors' => ['#94a3b8', '#334155'],
        ],
        [
            'name' => 'Ember Pour-Over Set',
            'description' => 'A borosilicate carafe and stainless steel dripper that brew a clean, bright cup. Dishwasher safe and built to last.',
            'price' => 64.00,
            'stock' => 0,
            'active' => true,
            'colors' => ['#f97316', '#7c2d12'],
        ],
        [
            'name' => 'Drift Cotton Towels',
            'description' => 'A set of two long-staple cotton towels with a honeycomb weave. Quick drying, generous in size and softer with every wash.',
            'price' => 34.00,
            'stock' => 95,
            'active' => true,
            'colors' => ['#22d3ee', '#155e75'],
        ],
        [
            'name' => 'Solstice Scented Candle',
            'description' => 'Cedar, bergamot and a hint of smoked vanilla in a reusable amber glass vessel. Burns cleanly for around 45 hours.',
            'price' => 29.00,
            'stock' => 70,
            'active' => true,
            'colors' => ['#facc15', '#854d0e'],
        ],
        [
            'name' => 'Vertex Notebook',
            'description' => 'A lay-flat A5 notebook with 160 pages of 100 gsm dot-grid paper. Hardcover, elastic closure and a back pocket.',
            'price' => 19.00,
            'stock' => 120,
            'active' => true,
            'colors' => ['#818cf8', '#312e81'],
        ],
        [
            'name' => 'Loom Woven Basket',
            'description' => 'Handwoven seagrass with leather handles. A generous catch-all for blankets, magazines or the endless small things.',
            'price' => 54.00,
            'stock' => 8,
            'active' => true,
            'colors' => ['#d6d3d1', '#57534e'],
        ],
        [
            'name' => 'Archive Canvas Tote',
            'description' => 'A retired sample from the 2024 collection, kept here to demonstrate hidden products in the admin panel.',
            'price' => 39.00,
            'stock' => 0,
            'active' => false,
            'colors' => ['#cbd5e1', '#1e293b'],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::PRODUCTS as $index => $product) {
            $slug = Str::slug($product['name']);
            $thumbnail = "products/{$slug}.svg";

            $this->storeThumbnail($thumbnail, $product['name'], $product['colors']);

            $record = Product::query()->updateOrCreate(
                ['product_slug' => $slug],
                [
                    'product_name' => $product['name'],
                    'description' => $product['description'],
                    'price' => $product['price'],
                    'stock' => $product['stock'],
                    'product_thumbnail' => $thumbnail,
                    'is_active' => $product['active'],
                ],
            );

            $record->forceFill([
                'created_at' => now()->subDays(count(self::PRODUCTS) - $index),
            ])->saveQuietly();
        }
    }

    /**
     * Write a lightweight SVG artwork for the demo product.
     *
     * @param  array{0: string, 1: string}  $colors
     */
    private function storeThumbnail(string $path, string $name, array $colors): void
    {
        $disk = Storage::disk('public');

        if ($disk->exists($path)) {
            return;
        }

        $initials = Str::upper(Str::substr(Str::slug($name), 0, 2));
        $label = e($name);
        $id = Str::slug($name);

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800" role="img" aria-label="{$label}">
    <defs>
        <linearGradient id="bg-{$id}" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="{$colors[0]}"/>
            <stop offset="100%" stop-color="{$colors[1]}"/>
        </linearGradient>
    </defs>
    <rect width="800" height="800" fill="url(#bg-{$id})"/>
    <circle cx="640" cy="160" r="220" fill="#ffffff" opacity="0.12"/>
    <circle cx="120" cy="700" r="260" fill="#000000" opacity="0.10"/>
    <text x="400" y="445" text-anchor="middle" font-family="Instrument Sans, ui-sans-serif, system-ui, sans-serif" font-size="220" font-weight="700" fill="#ffffff" opacity="0.92">{$initials}</text>
    <text x="400" y="560" text-anchor="middle" font-family="Instrument Sans, ui-sans-serif, system-ui, sans-serif" font-size="30" font-weight="500" fill="#ffffff" opacity="0.75">{$label}</text>
</svg>
SVG;

        $disk->put($path, $svg);
    }
}
