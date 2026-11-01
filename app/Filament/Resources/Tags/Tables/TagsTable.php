<?php

namespace App\Filament\Resources\Tags\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TagsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('tag_name')
            ->columns([
                TextColumn::make('tag_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                TextColumn::make('tag_slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('products_count')
                    ->label('Products')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-tag')
            ->emptyStateHeading('No tags yet')
            ->emptyStateDescription('Create tags to highlight products in the storefront.');
    }
}
