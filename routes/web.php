<?php

use App\Http\Controllers\TimeSheetController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $now = now();

    return view('timesheet.create', [
        'defaultTask' => 'Migrasi ESB ke Brigate dan SOAP ke REST API',
        'defaultStartDate' => $now->copy()->startOfMonth()->toDateString(),
        'defaultEndDate' => $now->copy()->endOfMonth()->toDateString(),
    ]);
});

Route::get('/timesheet', [TimeSheetController::class, 'index'])->name('timesheet.index');
Route::get('/timesheet/holidays', [TimeSheetController::class, 'holidays'])->name('timesheet.holidays');
Route::post('/timesheet/create', [TimeSheetController::class, 'store'])->name('timesheet.store');
