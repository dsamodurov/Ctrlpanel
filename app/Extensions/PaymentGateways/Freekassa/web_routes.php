<?php

use App\Extensions\PaymentGateways\Freekassa\FreekassaController;
use Illuminate\Support\Facades\Route;


Route::middleware(['web', 'auth'])->group(function () {
    Route::get('payment/freekassa/{shopProduct}', [FreekassaController::class, 'payment'])
        ->name('payment.FreekassaPay');
    Route::post('payment/freekassa/success', [FreekassaController::class, 'success'])
        ->name('payment.freekassa.success');
    Route::post('payment/freekassa/fail', [FreekassaController::class, 'fail'])
        ->name('payment.freekassa.fail');
});

Route::post('payment/freekassa/alert', [FreekassaController::class, 'alert'])
    ->name('payment.freekassa.alert');
