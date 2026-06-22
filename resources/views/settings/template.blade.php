<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $isNew ? 'Tambah' : 'Edit' }} Task Template</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            color: #111827;
            background:
                radial-gradient(circle at top left, rgba(79, 70, 229, .18), transparent 34rem),
                linear-gradient(180deg, #f8fafc 0%, #eef2ff 44%, #f8fafc 100%);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, sans-serif;
        }
        .app-shell { max-width: 720px; }
        .soft-card {
            border: 1px solid rgba(15, 23, 42, .08);
            background: rgba(255, 255, 255, .82);
            box-shadow: 0 18px 50px rgba(15, 23, 42, .08);
        }
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #2563eb);
            border: 0;
        }
    </style>
</head>
<body>
    <main class="container app-shell py-5">
        <a href="{{ route('settings.edit') }}" class="text-decoration-none small">&larr; Kembali</a>
        <h1 class="h3 fw-bold mb-4 mt-2">{{ $isNew ? 'Tambah' : 'Edit' }} Task Template</h1>

        <form method="post" action="{{ $isNew ? route('settings.template.store') : route('settings.template.update', ['template' => $template]) }}">
            @csrf
            @if (! $isNew) @method('PUT') @endif

            <div class="card soft-card rounded-4 border-0">
                <div class="card-body p-4 p-lg-5">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama template</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $template->name) }}" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">type_task</label>
                            <input type="text" name="type_task" class="form-control" value="{{ old('type_task', $template->type_task) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">id_project</label>
                            <input type="text" name="id_project" class="form-control" value="{{ old('id_project', $template->id_project) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">start_at</label>
                            <input type="time" name="start_at" class="form-control" value="{{ old('start_at', $template->start_at?->format('H:i') ?? '07:30') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">end_at</label>
                            <input type="time" name="end_at" class="form-control" value="{{ old('end_at', $template->end_at?->format('H:i') ?? '16:30') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">location</label>
                            <input type="text" name="location" class="form-control" value="{{ old('location', $template->location) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">skills</label>
                            <input type="text" name="skills" class="form-control" value="{{ old('skills', $template->skills) }}" required>
                        </div>
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="is_default" value="1" id="isDefault" @checked(old('is_default', $template->is_default))>
                        <label class="form-check-label fw-semibold" for="isDefault">Jadikan default</label>
                    </div>
                </div>
            </div>

            <div class="d-grid d-sm-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg px-4">Simpan</button>
                <a href="{{ route('settings.edit') }}" class="btn btn-link">Batal</a>
            </div>
        </form>
    </main>
</body>
</html>
