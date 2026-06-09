<?php

use App\Http\Controllers\Api\IpsController;
use App\Http\Controllers\Api\V1\CurrencyController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Middleware\AuthenticateApiKey;
use Illuminate\Support\Facades\Route;

Route::get('/ips.json', [IpsController::class, 'json']);
Route::get('/ips.js', [IpsController::class, 'js']);

Route::prefix('v1')->middleware([AuthenticateApiKey::class, 'throttle:api'])->group(function () {
    Route::get('/currencies', [CurrencyController::class, 'index']);
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::get('/invoices/{uuid}', [InvoiceController::class, 'show']);
    Route::get('/invoices/{uuid}/status', [InvoiceController::class, 'status']);
    Route::post('/invoices/{uuid}/cancel', [InvoiceController::class, 'cancel']);
});
