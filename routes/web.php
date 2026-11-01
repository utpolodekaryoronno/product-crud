<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SslCommerzPaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
Route::patch('/cart/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{product}', [CartController::class, 'destroy'])->name('cart.destroy');
Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');

Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/orders/{order}', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');

Route::get('/payment/sslcommerz/simulate/{order}', [SslCommerzPaymentController::class, 'simulate'])
    ->name('payments.sslcommerz.simulate');
Route::post('/payment/sslcommerz/simulate/{order}/success', [SslCommerzPaymentController::class, 'simulateSuccess'])
    ->name('payments.sslcommerz.simulate.success');
Route::post('/payment/sslcommerz/simulate/{order}/fail', [SslCommerzPaymentController::class, 'simulateFail'])
    ->name('payments.sslcommerz.simulate.fail');
Route::post('/payment/sslcommerz/simulate/{order}/cancel', [SslCommerzPaymentController::class, 'simulateCancel'])
    ->name('payments.sslcommerz.simulate.cancel');

Route::post('/payment/sslcommerz/success', [SslCommerzPaymentController::class, 'success'])
    ->name('payments.sslcommerz.success');
Route::post('/payment/sslcommerz/fail', [SslCommerzPaymentController::class, 'fail'])
    ->name('payments.sslcommerz.fail');
Route::post('/payment/sslcommerz/cancel', [SslCommerzPaymentController::class, 'cancel'])
    ->name('payments.sslcommerz.cancel');
Route::post('/payment/sslcommerz/ipn', [SslCommerzPaymentController::class, 'ipn'])
    ->name('payments.sslcommerz.ipn');
