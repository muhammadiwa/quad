<?php

namespace App\Http\Controllers;

use App\Models\QuadrangSetting;
use App\Models\TaskTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class SettingsController extends Controller
{
    private const PROTECTED_KEYS = ['base_url', 'csrf_token', 'cookie', 'user_agent'];

    public function tokenForm()
    {
        return view('settings.token');
    }

    public function tokenLogin(Request $request)
    {
        $expected = config('quadrang.admin_token');

        if (! $expected) {
            return back()->withErrors(['token' => 'QUADRANG_ADMIN_TOKEN belum diset di .env'])->withInput();
        }

        $data = $request->validate([
            'token' => ['required', 'string'],
        ]);

        if (! hash_equals($expected, $data['token'])) {
            return back()->withErrors(['token' => 'Token salah.'])->withInput();
        }

        return redirect()->route('settings.edit')
            ->cookie(Cookie::forever('quadrang_admin_token', $data['token']));
    }

    public function tokenLogout(Request $request)
    {
        return redirect()->route('settings.token.form')
            ->cookie(Cookie::forget('quadrang_admin_token'));
    }

    public function edit(Request $request)
    {
        $this->guardOrRedirect($request);

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
        $this->guardOrRedirect($request);

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

    private function guardOrRedirect(Request $request): void
    {
        $expected = config('quadrang.admin_token');
        if (! $expected) {
            abort(503, 'QUADRANG_ADMIN_TOKEN belum diset di .env');
        }

        $provided = $request->query('token') ?? $request->cookie('quadrang_admin_token');

        if (! $provided || ! hash_equals($expected, $provided)) {
            if ($request->expectsJson()) {
                abort(401, 'Token admin salah.');
            }

            redirect()->route('settings.token.form')->send();
            exit;
        }

        if (! $request->cookie('quadrang_admin_token')) {
            Cookie::queue(Cookie::forever('quadrang_admin_token', $provided));
        }
    }
}
