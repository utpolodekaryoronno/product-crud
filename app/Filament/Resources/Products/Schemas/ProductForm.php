<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product details')
                    ->description('The information customers see in the storefront.')
                    ->schema([
                        TextInput::make('product_name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, Set $set): void {
                                if ($operation === 'create') {
                                    $set('product_slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('product_slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(Product::class, 'product_slug', ignoreRecord: true)
                            ->helperText('Used in the public product URL.'),
                        Textarea::make('description')
                            ->rows(5)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Organisation')
                    ->description('Group this product by brand, category and tags.')
                    ->schema([
                        Select::make('brand_id')
                            ->label('Brand')
                            ->relationship('brand', 'brand_name')
                            ->searchable()
                            ->preload()
                            ->placeholder('No brand'),
                        Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'category_name')
                            ->searchable()
                            ->preload()
                            ->placeholder('No category'),
                        Select::make('tags')
                            ->label('Tags')
                            ->relationship('tags', 'tag_name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->placeholder('No tags'),
                    ])
                    ->columns(3),

                Section::make('Pricing & inventory')
                    ->description('Keep the price and stock level up to date.')
                    ->schema([
                        TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix(config('shop.currency_symbol'))
                            ->default(0),
                        TextInput::make('stock')
                            ->required()
                            ->integer()
                            ->minValue(0)
                            ->default(0),
                    ])
                    ->columns(2),

                Section::make('Media')
                    ->schema([
                        FileUpload::make('product_thumbnail')
                            ->label('Thumbnail')
                            ->image()
                            ->disk('public')
                            ->directory('products')
                            ->visibility('public')
                            ->imageEditor()
                            ->maxSize(2048)
                            ->helperText('PNG, JPG, GIF or WebP up to 2 MB. Square images look best.'),
                    ]),

                Section::make('Visibility')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Visible in storefront')
                            ->helperText('Hidden products stay in the admin panel but are not shown publicly.')
                            ->default(true),
                    ]),
            ]);
    }
}
