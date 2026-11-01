<?php

namespace App\Filament\Resources\Brands\Tables;

use App\Models\Brand;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class BrandsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('brand_name')
            ->columns([
                ImageColumn::make('logo_url')
                    ->label('Logo')
                    ->square()
                    ->size(40),
                TextColumn::make('brand_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Brand $record): string => Str::limit($record->brand_slug, 48)),
                TextColumn::make('products_count')
                    ->label('Products')
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Active'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Visibility')
                    ->options([
                        '1' => 'Visible',
                        '0' => 'Hidden',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Make visible')
                        ->icon('heroicon-m-eye')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true])),
                    BulkAction::make('deactivate')
                        ->label('Hide from storefront')
                        ->icon('heroicon-m-eye-slash')
                        ->color('warning')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false])),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-building-storefront')
            ->emptyStateHeading('No brands yet')
            ->emptyStateDescription('Create a brand to group products by maker or label.');
    }
}
