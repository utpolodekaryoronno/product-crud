<?php

namespace App\Filament\Resources\Tags\Schemas;

use App\Models\Tag;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tag details')
                    ->schema([
                        TextInput::make('tag_name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, Set $set): void {
                                if ($operation === 'create') {
                                    $set('tag_slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('tag_slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(Tag::class, 'tag_slug', ignoreRecord: true),
                    ])
                    ->columns(2),
            ]);
    }
}
