<?php

namespace App\Http\Controllers;

use App\Models\QuadrangSetting;
use App\Models\TaskTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    private const PROTECTED_KEYS = ['base_url', 'csrf_token', 'cookie', 'user_agent'];

    public function edit(Request $request)
    {
        $this->guard($request);

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
        $this->guard($request);

        $data = $request->validate([
            'settings' => ['array'],
            'settings.*' => ['nullable', 'string'],
            'default_template_id' => ['nullable', 'integer', 'exists:task_templates,id'],
        ]);

        foreach ($data['settings'] ?? [] as $key => $value) {
            QuadrangSetting::set($key, $value);
        }

        if (! empty($data['default_template_id'])) {
            TaskTemplate::where('id', $data['default_template_id'])->update(['is_default' => true]);
        }

        return back()->with('result', [
            'success' => true,
            'message' => 'Pengaturan tersimpan.',
        ]);
    }

    private function guard(Request $request): void
    {
        $expected = config('quadrang.admin_token');
        if (! $expected) {
            abort(503, 'QUADRANG_ADMIN_TOKEN belum diset di .env');
        }

        $provided = $request->query('token') ?? $request->cookie('quadrang_admin_token');

        if (! $provided || ! hash_equals($expected, $provided)) {
            abort(401, 'Token admin salah.');
        }

        cookie()->queue(cookie()->forever('quadrang_admin_token', $provided));
    }
}
