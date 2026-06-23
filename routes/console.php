<?php

use App\Models\QuadrangSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('attendance:auto')
    ->everyMinute()
    ->when(fn () => in_array(strtolower((string) QuadrangSetting::get('auto_attendance_enabled', '0')), ['1', 'true', 'yes', 'on'], true))
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/attendance-auto.log'));
