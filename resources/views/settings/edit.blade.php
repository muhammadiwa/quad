<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quadrang Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --ink: #111827;
            --muted: #64748b;
            --line: rgba(15, 23, 42, .08);
            --brand: #4f46e5;
            --surface: rgba(255, 255, 255, .82);
        }
        body {
            min-height: 100vh;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(79, 70, 229, .18), transparent 34rem),
                radial-gradient(circle at top right, rgba(14, 165, 233, .16), transparent 30rem),
                linear-gradient(180deg, #f8fafc 0%, #eef2ff 44%, #f8fafc 100%);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, sans-serif;
        }
        .app-shell { max-width: 1080px; }
        .soft-card {
            border: 1px solid var(--line);
            background: var(--surface);
            box-shadow: 0 18px 50px rgba(15, 23, 42, .08);
            backdrop-filter: blur(14px);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--brand), #2563eb);
            border: 0;
        }
        code { font-size: .8rem; color: #4338ca; }
    </style>
</head>
<body>
    <main class="container app-shell py-5">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <a href="{{ route('timesheet.index') }}" class="text-decoration-none small">&larr; Kembali</a>
                <h1 class="h3 fw-bold mb-0 mt-2">Quadrang Settings</h1>
            </div>
            <span class="badge text-bg-light border px-3 py-2">local-only</span>
        </div>

        @if (session('result'))
            <div class="alert alert-{{ session('result.success') ? 'success' : 'danger' }} soft-card">
                {{ session('result.message') }}
            </div>
        @endif

        <form method="post" action="{{ route('settings.update') }}">
            @csrf
            @method('PATCH')

            <div class="card soft-card rounded-4 border-0 mb-4">
                <div class="card-body p-4 p-lg-5">
                    <h2 class="h5 fw-bold mb-3">Credentials</h2>
                    <p class="text-secondary small mb-4">
                        Salin nilai <code>Cookie</code> dan <code>X-CSRFToken</code> dari DevTools browser setelah login di Quadrang.
                    </p>

                    @php
                        $settingKeys = [
                            'base_url',
                            'csrf_token',
                            'cookie',
                            'user_agent',
                            'default_task_description',
                            'default_lat',
                            'default_lon',
                            'auto_attendance_enabled',
                            'auto_attendance_timezone',
                            'auto_clock_in_time',
                            'auto_clock_out_time',
                            'auto_attendance_window_minutes',
                        ];
                        $descriptionFallbacks = [
                            'auto_attendance_enabled' => 'Set 1 untuk mengaktifkan cron auto clock in/out, 0 untuk mematikan.',
                            'auto_attendance_timezone' => 'Timezone scheduler attendance, contoh: Asia/Jakarta.',
                            'auto_clock_in_time' => 'Override jam clock in otomatis. Kosongkan untuk mengikuti start_at template default.',
                            'auto_clock_out_time' => 'Override jam clock out otomatis. Kosongkan untuk mengikuti end_at template default.',
                            'auto_attendance_window_minutes' => 'Toleransi menit setelah jam target. Default 5 menit.',
                        ];
                    @endphp

                    @foreach ($settingKeys as $key)
                        @php
                            $setting = $settings[$key] ?? null;
                            $value = old("settings.$key", $setting->value ?? '');
                            $description = $setting?->description ?? $descriptionFallbacks[$key] ?? '';
                        @endphp
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                {{ $key }}
                                @if (in_array($key, $protectedKeys))
                                    <span class="badge text-bg-warning ms-1">sensitive</span>
                                @endif
                            </label>
                            @if ($key === 'auto_attendance_enabled')
                                <input type="hidden" name="settings[{{ $key }}]" value="0">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="{{ $key }}" name="settings[{{ $key }}]" value="1"
                                        @checked(in_array((string) $value, ['1', 'true', 'on', 'yes'], true))>
                                    <label class="form-check-label" for="{{ $key }}">Aktifkan auto attendance</label>
                                </div>
                            @elseif (in_array($key, ['auto_clock_in_time', 'auto_clock_out_time']))
                                <input type="time" name="settings[{{ $key }}]"
                                    class="form-control font-monospace"
                                    placeholder="{{ $description }}"
                                    value="{{ $value }}">
                            @elseif (in_array($key, ['default_lat', 'default_lon']))
                                <input type="number" step="any" name="settings[{{ $key }}]"
                                    class="form-control font-monospace"
                                    placeholder="{{ $description }}"
                                    value="{{ $value }}">
                            @elseif ($key === 'auto_attendance_window_minutes')
                                <input type="number" min="1" max="60" step="1" name="settings[{{ $key }}]"
                                    class="form-control font-monospace"
                                    placeholder="{{ $description }}"
                                    value="{{ $value }}">
                            @else
                                <textarea name="settings[{{ $key }}]" rows="{{ $key === 'cookie' || $key === 'user_agent' ? 3 : 1 }}"
                                    class="form-control font-monospace"
                                    placeholder="{{ $description }}">{{ $value }}</textarea>
                            @endif
                            @if ($description)
                                <div class="form-text">{{ $description }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="d-grid d-sm-flex gap-2">
                <button type="submit" class="btn btn-primary btn-lg px-4">Simpan Pengaturan</button>
                <a href="{{ route('timesheet.index') }}" class="btn btn-link">Batal</a>
            </div>
        </form>

        <div class="card soft-card rounded-4 border-0 mt-4">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 fw-bold mb-0">Task Templates</h2>
                    <a href="{{ route('settings.template.create') }}" class="btn btn-sm btn-outline-primary">+ Tambah template</a>
                </div>
                <p class="text-secondary small mb-3">
                    Template <strong>default</strong> dipakai saat membuat task. Klik "Jadikan Default" untuk mengganti.
                </p>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="small text-secondary">
                            <tr>
                                <th>Nama</th>
                                <th>Project</th>
                                <th>Jam kerja</th>
                                <th>Location</th>
                                <th>Skills</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($templates as $tpl)
                                <tr class="{{ $tpl->is_default ? 'table-primary' : '' }}">
                                    <td>
                                        <a href="{{ route('settings.template.edit', $tpl) }}" class="text-decoration-none fw-semibold">
                                            {{ $tpl->name }}
                                        </a>
                                        @if ($tpl->is_default)
                                            <span class="badge text-bg-primary ms-2">Default</span>
                                        @endif
                                    </td>
                                    <td><code>{{ $tpl->id_project }}</code></td>
                                    <td>{{ $tpl->start_at->format('H:i') }} - {{ $tpl->end_at->format('H:i') }}</td>
                                    <td>{{ $tpl->location }}</td>
                                    <td><code>{{ $tpl->skills }}</code></td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2 align-items-center">
                                            @if (! $tpl->is_default)
                                                <form method="post" action="{{ route('settings.update') }}" class="m-0">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="default_template_id" value="{{ $tpl->id }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Jadikan Default</button>
                                                </form>
                                            @endif
                                            <a href="{{ route('settings.template.edit', $tpl) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                            @if (! $tpl->is_default)
                                                <form method="post" action="{{ route('settings.template.destroy', $tpl) }}" class="m-0">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('Hapus template {{ $tpl->name }}?')">
                                                        Hapus
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-secondary py-4">Belum ada template.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
