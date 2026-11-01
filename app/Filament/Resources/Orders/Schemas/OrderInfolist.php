<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Support\Money;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order')
                    ->schema([
                        TextEntry::make('order_number')
                            ->label('Order number')
                            ->copyable(),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                            ->color(fn (OrderStatus $state): string => $state->color()),
                        TextEntry::make('total_amount')
                            ->label('Total')
                            ->formatStateUsing(fn (string $state): string => Money::format($state)),
                        TextEntry::make('currency')
                            ->label('Currency'),
                        TextEntry::make('created_at')
                            ->label('Placed')
                            ->dateTime(),
                        TextEntry::make('paid_at')
                            ->label('Paid')
                            ->dateTime()
                            ->placeholder('Not paid yet'),
                    ])
                    ->columns(3),

                Section::make('Customer')
                    ->schema([
                        TextEntry::make('customer_name')
                            ->label('Name'),
                        TextEntry::make('customer_email')
                            ->label('Email')
                            ->copyable(),
                        TextEntry::make('customer_phone')
                            ->label('Phone')
                            ->copyable(),
                        TextEntry::make('shipping_address')
                            ->label('Address')
                            ->columnSpan(2),
                        TextEntry::make('city')
                            ->label('City'),
                        TextEntry::make('postcode')
                            ->label('Postcode')
                            ->placeholder('—'),
                        TextEntry::make('notes')
                            ->label('Notes')
                            ->columnSpanFull()
                            ->placeholder('No notes'),
                    ])
                    ->columns(3),

                Section::make('Payment')
                    ->schema([
                        TextEntry::make('payment_gateway')
                            ->label('Gateway')
                            ->placeholder('—'),
                        TextEntry::make('tran_id')
                            ->label('Transaction')
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('val_id')
                            ->label('Validation')
                            ->copyable()
                            ->placeholder('—'),
                    ])
                    ->columns(3),

                Section::make('Items')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->schema([
                                TextEntry::make('product_name')
                                    ->label('Product'),
                                TextEntry::make('quantity')
                                    ->label('Qty'),
                                TextEntry::make('unit_price')
                                    ->label('Unit price')
                                    ->formatStateUsing(fn (string $state): string => Money::format($state)),
                                TextEntry::make('line_total')
                                    ->label('Line total')
                                    ->formatStateUsing(fn (string $state): string => Money::format($state)),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }
}
