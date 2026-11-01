<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category details')
                    ->schema([
                        TextInput::make('category_name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, Set $set): void {
                                if ($operation === 'create') {
                                    $set('category_slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('category_slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(Category::class, 'category_slug', ignoreRecord: true),
                        Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Category image')
                    ->schema([
                        FileUpload::make('category_image')
                            ->label('Image')
                            ->image()
                            ->disk('public')
                            ->directory('categories')
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
