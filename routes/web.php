<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\SpendingPlanController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware('auth')->group(function () {
    Route::get('/plan', [SpendingPlanController::class, 'show'])->name('plan.show');
    Route::get('/plan/data', [SpendingPlanController::class, 'data'])->name('plan.data');
    Route::post('/plan', [SpendingPlanController::class, 'store'])->name('plan.store');
    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
});
