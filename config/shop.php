<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storefront currency
    |--------------------------------------------------------------------------
    |
    | The currency used to display prices and to charge customers through the
    | payment gateway. SSLCommerz merchants are usually configured for BDT,
    | so set SHOP_CURRENCY=BDT and SHOP_CURRENCY_SYMBOL="৳" when selling in
    | Bangladesh.
    |
    */

    'currency' => env('SHOP_CURRENCY', 'USD'),

    'currency_symbol' => env('SHOP_CURRENCY_SYMBOL', '$'),

];
