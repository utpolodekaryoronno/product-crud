<?php

namespace App\Support;

class Money
{
    /**
     * Format an amount using the configured storefront currency symbol.
     */
    public static function format(float|int|string $amount): string
    {
        return config('shop.currency_symbol').number_format((float) $amount, 2);
    }

    /**
     * Get the currency code used by the storefront and payment gateway.
     */
    public static function currency(): string
    {
        return (string) config('shop.currency');
    }
}
