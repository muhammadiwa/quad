<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quadrang Settings - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --brand: #4f46e5; }
        body {
            min-height: 100vh;
            color: #111827;
            background:
                radial-gradient(circle at top left, rgba(79, 70, 229, .18), transparent 34rem),
                radial-gradient(circle at top right, rgba(14, 165, 233, .16), transparent 30rem),
                linear-gradient(180deg, #f8fafc 0%, #eef2ff 44%, #f8fafc 100%);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, sans-serif;
        }
        .app-shell { max-width: 460px; }
        .soft-card {
            border: 1px solid rgba(15, 23, 42, .08);
            background: rgba(255, 255, 255, .82);
            box-shadow: 0 18px 50px rgba(15, 23, 42, .08);
            backdrop-filter: blur(14px);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--brand), #2563eb);
            border: 0;
        }
    </style>
</head>
<body>
    <main class="container app-shell py-5">
        <div class="text-center mb-4">
            <span class="badge text-bg-light border px-3 py-2">Quadrang</span>
            <h1 class="h3 fw-bold mt-3 mb-1">Settings</h1>
            <p class="text-secondary small mb-0">Masukkan admin token untuk lanjut.</p>
        </div>

        <div class="card soft-card rounded-4 border-0">
            <div class="card-body p-4 p-lg-5">
                @if ($errors->any())
                    <div class="alert alert-danger small">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="post" action="{{ route('settings.token.login') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Admin Token</label>
                        <input type="password" name="token" class="form-control form-control-lg" autofocus required>
                        <div class="form-text">
                            Diset di <code>.env</code> sebagai <code>QUADRANG_ADMIN_TOKEN</code>.
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100">Masuk</button>
                </form>
            </div>
        </div>

        <div class="text-center mt-3">
            <a href="{{ route('timesheet.index') }}" class="small text-decoration-none">&larr; Kembali ke timesheet</a>
        </div>
    </main>
</body>
</html>
