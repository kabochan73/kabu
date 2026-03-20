<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/stocks', [StockController::class, 'store'])->name('stocks.store');
Route::get('/stocks/{stock}', [StockController::class, 'show'])->name('stocks.show');
Route::delete('/stocks/{stock}', [StockController::class, 'destroy'])->name('stocks.destroy');
