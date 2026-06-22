<?php

namespace App\Http\Controllers;

use App\Models\TaskTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class TaskTemplateController extends Controller
{
    public function create(Request $request)
    {
        $this->guardOrRedirect($request);

        return view('settings.template', [
            'template' => new TaskTemplate([
                'type_task' => 'Work',
                'id_project' => '17',
                'start_at' => '07:30',
                'end_at' => '16:30',
                'location' => 'On Site',
                'skills' => '70',
            ]),
            'isNew' => true,
        ]);
    }

    public function edit(Request $request, TaskTemplate $template)
    {
        $this->guardOrRedirect($request);

        return view('settings.template', [
            'template' => $template,
            'isNew' => false,
        ]);
    }

    public function store(Request $request)
    {
        $this->guardOrRedirect($request);
        $data = $this->validateData($request);

        TaskTemplate::create($data);

        return redirect()->route('settings.edit')
            ->with('result', ['success' => true, 'message' => 'Template dibuat.']);
    }

    public function update(Request $request, TaskTemplate $template)
    {
        $this->guardOrRedirect($request);
        $data = $this->validateData($request);

        $template->update($data);

        return redirect()->route('settings.edit')
            ->with('result', ['success' => true, 'message' => 'Template diperbarui.']);
    }

    public function destroy(Request $request, TaskTemplate $template)
    {
        $this->guardOrRedirect($request);

        if ($template->is_default) {
            return back()->with('result', [
                'success' => false,
                'message' => 'Tidak bisa hapus template default. Set template lain sebagai default dulu.',
            ]);
        }

        $template->delete();

        return redirect()->route('settings.edit')
            ->with('result', ['success' => true, 'message' => 'Template dihapus.']);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type_task' => ['required', 'string', 'max:50'],
            'id_project' => ['required', 'string', 'max:50'],
            'start_at' => ['required', 'date_format:H:i'],
            'end_at' => ['required', 'date_format:H:i', 'after:start_at'],
            'location' => ['required', 'string', 'max:100'],
            'skills' => ['required', 'string', 'max:50'],
            'is_default' => ['nullable', 'boolean'],
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
