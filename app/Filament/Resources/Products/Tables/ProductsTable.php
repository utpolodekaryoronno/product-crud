<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('thumbnail_url')
                    ->label('Image')
                    ->square()
                    ->size(48),
                TextColumn::make('product_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Product $record): string => Str::limit($record->product_slug, 48)),
                TextColumn::make('brand.brand_name')
                    ->label('Brand')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('category.category_name')
                    ->label('Category')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('tags.tag_name')
                    ->label('Tags')
                    ->badge()
                    ->limitList(2)
                    ->toggleable(),
                TextColumn::make('price')
                    ->money('usd')
                    ->sortable(),
                TextColumn::make('stock')
                    ->badge()
                    ->sortable()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 5 => 'warning',
                        default => 'success',
                    }),
                ToggleColumn::make('is_active')
                    ->label('Active'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('brand_id')
                    ->label('Brand')
                    ->relationship('brand', 'brand_name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'category_name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('tags')
                    ->label('Tag')
                    ->relationship('tags', 'tag_name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('is_active')
                    ->label('Visibility')
                    ->options([
                        '1' => 'Visible',
                        '0' => 'Hidden',
                    ]),
                Filter::make('price_range')
                    ->label('Price range')
                    ->schema([
                        TextInput::make('price_from')
                            ->label('From')
                            ->numeric()
                            ->prefix('$'),
                        TextInput::make('price_to')
                            ->label('To')
                            ->numeric()
                            ->prefix('$'),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['price_from'] ?? null, fn (Builder $query, $value) => $query->where('price', '>=', $value))
                        ->when($data['price_to'] ?? null, fn (Builder $query, $value) => $query->where('price', '<=', $value))),
                Filter::make('out_of_stock')
                    ->label('Out of stock only')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('stock', '<=', 0)),
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
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
                        ->label('Hide from storefront')
                        ->icon('heroicon-m-eye-slash')
                        ->color('warning')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-shopping-bag')
            ->emptyStateHeading('No products yet')
            ->emptyStateDescription('Create your first product to start building the storefront.')
            ->emptyStateActions([
                CreateAction::make(),
            ]);
    }
}
