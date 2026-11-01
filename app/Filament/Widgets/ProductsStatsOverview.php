<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductsStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static ?bool $isLazyLoaded = false;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $total = Product::query()->count();
        $active = Product::query()->where('is_active', true)->count();
        $outOfStock = Product::query()->where('stock', '<=', 0)->count();
        $lowStock = Product::query()->whereBetween('stock', [1, 5])->count();

        $inventoryValue = (float) Product::query()
            ->selectRaw('COALESCE(SUM(price * stock), 0) as value')
            ->value('value');

        return [
            Stat::make('Total products', number_format($total))
                ->description("{$active} visible in the storefront")
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),
            Stat::make('Inventory value', Money::format($inventoryValue))
                ->description('Retail value of stock on hand')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
            Stat::make('Low stock', number_format($lowStock))
                ->description($lowStock > 0 ? 'Five units or fewer remaining' : 'Everything is well stocked')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStock > 0 ? 'warning' : 'success'),
            Stat::make('Out of stock', number_format($outOfStock))
                ->description($outOfStock > 0 ? 'Restock these products soon' : 'No sold-out products')
                ->descriptionIcon($outOfStock > 0 ? 'heroicon-m-x-circle' : 'heroicon-m-check-circle')
                ->color($outOfStock > 0 ? 'danger' : 'success'),
        ];
    }
}
