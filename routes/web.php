<?php

use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TaskTemplateController;
use App\Http\Controllers\TimeSheetController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('timesheet.index');
});

Route::get('/timesheet', [TimeSheetController::class, 'index'])->name('timesheet.index');
Route::get('/timesheet/holidays', [TimeSheetController::class, 'holidays'])->name('timesheet.holidays');
Route::post('/timesheet/create', [TimeSheetController::class, 'store'])->name('timesheet.store');
Route::post('/api/timesheet/create', [TimeSheetController::class, 'create'])->name('timesheet.api.create');

Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [SettingsController::class, 'edit'])->name('edit');
    Route::post('/', [SettingsController::class, 'update'])->name('update');

    Route::get('/template/new', [TaskTemplateController::class, 'create'])->name('template.create');
    Route::post('/template', [TaskTemplateController::class, 'store'])->name('template.store');
    Route::get('/template/{template}', [TaskTemplateController::class, 'edit'])->name('template.edit');
    Route::put('/template/{template}', [TaskTemplateController::class, 'update'])->name('template.update');
    Route::delete('/template/{template}', [TaskTemplateController::class, 'destroy'])->name('template.destroy');
});
