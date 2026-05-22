<?php

use App\Http\Controllers\TimeSheetController;
use Illuminate\Support\Facades\Route;

Route::post('/timesheet/create', [TimesheetController::class, 'create']);
