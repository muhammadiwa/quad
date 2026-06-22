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
