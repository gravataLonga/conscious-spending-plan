<?php

use App\Http\Controllers\SpendingPlanController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SpendingPlanController::class, 'show']);
Route::get('/plan', [SpendingPlanController::class, 'data'])->name('plan.data');
Route::post('/plan', [SpendingPlanController::class, 'store'])->name('plan.store');
