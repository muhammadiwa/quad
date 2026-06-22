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
            'https://quadrang.steradian.co.id',
            'Quadrang site base URL'
        );

        QuadrangSetting::set(
            'csrf_token',
            '',
            'X-CSRFToken header value. Salin dari DevTools browser setelah login di Quadrang, lalu simpan dari /settings.'
        );

        QuadrangSetting::set(
            'cookie',
            '',
            'Cookie header lengkap. Salin dari DevTools browser setelah login, lalu simpan dari /settings.'
        );

        QuadrangSetting::set(
            'default_task_description',
            'Migrasi ESB ke Brigate dan SOAP ke REST API',
            'Deskripsi task default yang di-prefill di form /timesheet.'
        );

        QuadrangSetting::set(
            'user_agent',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'HTTP User-Agent header yang dikirim ke Quadrang.'
        );

        QuadrangSetting::set(
            'default_lat',
            '',
            'Latitude default untuk Clock In / Clock Out. Kosongkan agar browser pakai GPS saat itu juga.'
        );

        QuadrangSetting::set(
            'default_lon',
            '',
            'Longitude default untuk Clock In / Clock Out. Kosongkan agar browser pakai GPS saat itu juga.'
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
