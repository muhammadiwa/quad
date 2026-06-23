<?php

namespace App\Http\Controllers;

use App\Models\QuadrangSetting;
use App\Models\TaskTemplate;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    private const PROTECTED_KEYS = ['base_url', 'csrf_token', 'cookie', 'user_agent'];

    public function edit()
    {
        $settings = QuadrangSetting::orderBy('key')->get()->keyBy('key');
        $templates = TaskTemplate::orderByDesc('is_default')->orderBy('name')->get();
        $defaultTemplate = TaskTemplate::default();

        return view('settings.edit', [
            'settings' => $settings,
            'templates' => $templates,
            'defaultTemplate' => $defaultTemplate,
            'protectedKeys' => self::PROTECTED_KEYS,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'settings' => ['array'],
            'settings.*' => ['nullable', 'string'],
            'default_template_id' => ['nullable', 'integer', 'exists:task_templates,id'],
            'settings.default_lat' => ['nullable', 'string', 'regex:/^-?\d+(\.\d+)?$/'],
            'settings.default_lon' => ['nullable', 'string', 'regex:/^-?\d+(\.\d+)?$/'],
            'settings.auto_attendance_enabled' => ['nullable', 'string', 'in:0,1'],
            'settings.auto_attendance_timezone' => ['nullable', 'string', 'timezone'],
            'settings.auto_clock_in_time' => ['nullable', 'string', 'date_format:H:i'],
            'settings.auto_clock_out_time' => ['nullable', 'string', 'date_format:H:i'],
            'settings.auto_attendance_window_minutes' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);

        foreach ($data['settings'] ?? [] as $key => $value) {
            QuadrangSetting::set($key, $value);
        }

        if (! empty($data['default_template_id'])) {
            $template = TaskTemplate::find($data['default_template_id']);
            if ($template) {
                $template->update(['is_default' => true]);
            }
        }

        return back()->with('result', [
            'success' => true,
            'message' => 'Pengaturan tersimpan.',
        ]);
    }
}
