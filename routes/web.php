<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\SpendingPlanController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware('auth')->group(function () {
    Route::get('/plan', [SpendingPlanController::class, 'show'])->name('plan.show');
    Route::get('/plan/data', [SpendingPlanController::class, 'data'])->name('plan.data');
    Route::post('/plan', [SpendingPlanController::class, 'store'])->name('plan.store');
    Route::get('/plan/snapshots/summary', [SpendingPlanController::class, 'snapshotSummary'])->name('plan.snapshots.summary');
    Route::get('/plan/snapshots/summary/data', [SpendingPlanController::class, 'snapshotSummaryData'])->name('plan.snapshots.summary.data');
    Route::get('/plan/snapshots', [SpendingPlanController::class, 'snapshots'])->name('plan.snapshots');
    Route::get('/plan/snapshots/{snapshot}', [SpendingPlanController::class, 'showSnapshot'])->name('plan.snapshots.show');
    Route::post('/plan/snapshots', [SpendingPlanController::class, 'storeSnapshot'])->name('plan.snapshots.store');
    Route::get('/plan/export/csv', [SpendingPlanController::class, 'exportCsv'])->name('plan.export.csv');
    Route::get('/plan/export/pdf', [SpendingPlanController::class, 'exportPdf'])->name('plan.export.pdf');
    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
});
