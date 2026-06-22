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
            env('QUADRANG_BASE_URL', 'https://quadrang.steradian.co.id'),
            'Quadrang site base URL'
        );

        QuadrangSetting::set(
            'csrf_token',
            env('QUADRANG_CSRF_TOKEN', ''),
            'X-CSRFToken header value, copy from browser DevTools after login'
        );

        QuadrangSetting::set(
            'cookie',
            env('QUADRANG_COOKIE', ''),
            'Full Cookie header value, copy from browser DevTools'
        );

        QuadrangSetting::set(
            'default_task_description',
            env('QUADRANG_DEFAULT_TASK', 'Migrasi ESB ke Brigate dan SOAP ke REST API'),
            'Default task description prefilled in the form'
        );

        QuadrangSetting::set(
            'user_agent',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'HTTP User-Agent header sent to Quadrang'
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
