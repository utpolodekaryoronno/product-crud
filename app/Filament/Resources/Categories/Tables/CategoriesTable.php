<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Models\Category;
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

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('category_name')
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Image')
                    ->square()
                    ->size(40),
                TextColumn::make('category_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Category $record): string => Str::limit($record->category_slug, 48)),
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
            ->emptyStateIcon('heroicon-o-squares-2x2')
            ->emptyStateHeading('No categories yet')
            ->emptyStateDescription('Create a category to organise the catalogue.');
    }
}
