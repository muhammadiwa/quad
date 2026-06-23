<?php

namespace Database\Seeders;

use App\Models\QuadrangSetting;
use App\Models\TaskTemplate;
use Illuminate\Database\Seeder;

class QuadrangSeeder extends Seeder
{
    public function run(): void
    {
        QuadrangSetting::set(
            'base_url',
            QuadrangSetting::get('base_url', 'https://quadrang.steradian.co.id'),
            'Quadrang site base URL'
        );

        QuadrangSetting::set(
            'csrf_token',
            QuadrangSetting::get('csrf_token', ''),
            'X-CSRFToken header value. Salin dari DevTools browser setelah login di Quadrang, lalu simpan dari /settings.'
        );

        QuadrangSetting::set(
            'cookie',
            QuadrangSetting::get('cookie', ''),
            'Cookie header lengkap. Salin dari DevTools browser setelah login, lalu simpan dari /settings.'
        );

        QuadrangSetting::set(
            'default_task_description',
            QuadrangSetting::get('default_task_description', 'Migrasi ESB ke Brigate dan SOAP ke REST API'),
            'Deskripsi task default yang di-prefill di form /timesheet.'
        );

        QuadrangSetting::set(
            'user_agent',
            QuadrangSetting::get('user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'),
            'HTTP User-Agent header yang dikirim ke Quadrang.'
        );

        QuadrangSetting::set(
            'default_lat',
            QuadrangSetting::get('default_lat', ''),
            'Latitude default untuk Clock In / Clock Out. Kosongkan agar browser pakai GPS saat itu juga.'
        );

        QuadrangSetting::set(
            'default_lon',
            QuadrangSetting::get('default_lon', ''),
            'Longitude default untuk Clock In / Clock Out. Kosongkan agar browser pakai GPS saat itu juga.'
        );

        QuadrangSetting::set(
            'auto_attendance_enabled',
            QuadrangSetting::get('auto_attendance_enabled', '0'),
            'Set 1 untuk mengaktifkan cron auto clock in/out, 0 untuk mematikan.'
        );

        QuadrangSetting::set(
            'auto_attendance_timezone',
            QuadrangSetting::get('auto_attendance_timezone', 'Asia/Jakarta'),
            'Timezone scheduler attendance, contoh: Asia/Jakarta.'
        );

        QuadrangSetting::set(
            'auto_clock_in_time',
            QuadrangSetting::get('auto_clock_in_time', ''),
            'Override jam clock in otomatis. Kosongkan untuk mengikuti start_at template default.'
        );

        QuadrangSetting::set(
            'auto_clock_out_time',
            QuadrangSetting::get('auto_clock_out_time', ''),
            'Override jam clock out otomatis. Kosongkan untuk mengikuti end_at template default.'
        );

        QuadrangSetting::set(
            'auto_attendance_window_minutes',
            QuadrangSetting::get('auto_attendance_window_minutes', '5'),
            'Toleransi menit setelah jam target. Default 5 menit.'
        );

        if (! TaskTemplate::where('is_default', true)->exists()) {
            TaskTemplate::create([
                'name' => 'Default Work Task',
                'type_task' => 'Work',
                'id_project' => '17',
                'start_at' => '07:30:00',
                'end_at' => '16:30:00',
                'location' => 'On Site',
                'skills' => '70',
                'is_default' => true,
            ]);
        }
    }
}
