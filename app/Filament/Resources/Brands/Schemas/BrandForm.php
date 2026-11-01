<?php

namespace App\Filament\Resources\Brands\Schemas;

use App\Models\Brand;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Brand details')
                    ->schema([
                        TextInput::make('brand_name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, Set $set): void {
                                if ($operation === 'create') {
                                    $set('brand_slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('brand_slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(Brand::class, 'brand_slug', ignoreRecord: true),
                        Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Branding')
                    ->schema([
                        FileUpload::make('brand_logo')
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->directory('brands')
                            ->visibility('public')
                            ->imageEditor()
                            ->maxSize(2048),
                        Toggle::make('is_active')
                            ->label('Visible in storefront')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
