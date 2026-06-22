<?php

namespace App\Http\Controllers;

use App\Models\TaskTemplate;
use Illuminate\Http\Request;

class TaskTemplateController extends Controller
{
    public function create()
    {
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

    public function edit(TaskTemplate $template)
    {
        return view('settings.template', [
            'template' => $template,
            'isNew' => false,
        ]);
    }

    public function store(Request $request)
    {
        TaskTemplate::create($this->validateData($request));

        return redirect()->route('settings.edit')
            ->with('result', ['success' => true, 'message' => 'Template dibuat.']);
    }

    public function update(Request $request, TaskTemplate $template)
    {
        $template->update($this->validateData($request));

        return redirect()->route('settings.edit')
            ->with('result', ['success' => true, 'message' => 'Template diperbarui.']);
    }

    public function destroy(TaskTemplate $template)
    {
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
}
