<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quadrang Attendance</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --ink: #111827;
            --muted: #64748b;
            --line: rgba(15, 23, 42, .08);
            --brand: #4f46e5;
            --surface: rgba(255, 255, 255, .82);
            --ok: #16a34a;
            --warn: #f59e0b;
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
        .app-shell { max-width: 760px; }
        .soft-card {
            border: 1px solid var(--line);
            background: var(--surface);
            box-shadow: 0 18px 50px rgba(15, 23, 42, .08);
            backdrop-filter: blur(14px);
        }
        .btn-ok {
            background: linear-gradient(135deg, #16a34a, #15803d);
            border: 0;
            color: #fff;
        }
        .btn-ok:hover { color: #fff; opacity: .92; }
        .btn-warn {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border: 0;
            color: #fff;
        }
        .btn-warn:hover { color: #fff; opacity: .92; }
        .status-dot {
            width: .65rem;
            height: .65rem;
            display: inline-block;
            border-radius: 999px;
            background: var(--ok);
            box-shadow: 0 0 0 6px rgba(22, 163, 74, .16);
        }
        code { font-size: .85rem; color: #4338ca; }
    </style>
</head>
<body>
    <main class="container app-shell py-5">
        <div class="d-flex justify-content-between mb-4">
            <a href="{{ route('timesheet.index') }}" class="text-decoration-none small">&larr; Timesheet</a>
            <a href="{{ route('settings.edit') }}" class="text-decoration-none small">⚙ Settings</a>
        </div>

        <div class="card soft-card rounded-4 border-0 mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="status-dot"></span>
                    <span class="small fw-semibold text-uppercase">Quadrang attendance</span>
                </div>
                <h1 class="display-6 fw-bold mb-2">Clock In / Clock Out</h1>
                <p class="text-secondary mb-0">
                    Klik tombol di bawah. Browser akan minta izin lokasi, lalu kami forward ke
                    Quadrang sebagai <code>?lat=...&amp;lon=...</code> pakai session cookie Anda.
                </p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="card soft-card rounded-4 border-0 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span style="font-size: 1.5rem;">🟢</span>
                            <h2 class="h5 fw-bold mb-0">Clock In</h2>
                        </div>
                        <p class="text-secondary small mb-3">Catat kehadiran masuk hari ini.</p>
                        <button id="btnClockIn" class="btn btn-ok btn-lg w-100">
                            <span class="spinner-border spinner-border-sm d-none me-2" id="spinIn"></span>
                            Clock In
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card soft-card rounded-4 border-0 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span style="font-size: 1.5rem;">🔴</span>
                            <h2 class="h5 fw-bold mb-0">Clock Out</h2>
                        </div>
                        <p class="text-secondary small mb-3">Catat kehadiran keluar hari ini.</p>
                        <button id="btnClockOut" class="btn btn-warn btn-lg w-100">
                            <span class="spinner-border spinner-border-sm d-none me-2" id="spinOut"></span>
                            Clock Out
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="lastLocation" class="text-center text-secondary small mt-4"></div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const lastLocEl = document.getElementById('lastLocation');

        async function call(action, btn, spinner) {
            if (!navigator.geolocation) {
                Swal.fire('Error', 'Browser tidak mendukung geolocation.', 'error');
                return;
            }

            btn.disabled = true;
            spinner.classList.remove('d-none');
            btn.blur();

            navigator.geolocation.getCurrentPosition(async (pos) => {
                const lat = pos.coords.latitude;
                const lon = pos.coords.longitude;
                const acc = pos.coords.accuracy;

                lastLocEl.innerHTML = `Lokasi terakhir: <code>${lat.toFixed(6)}, ${lon.toFixed(6)}</code> &plusmn; ${Math.round(acc)} m`;

                try {
                    const res = await fetch(action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRFToken': CSRF,
                        },
                        body: JSON.stringify({ lat, lon }),
                    });

                    const data = await res.json();

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: data.message,
                            footer: `<small>lat=${lat.toFixed(6)}, lon=${lon.toFixed(6)}</small>`,
                            confirmButtonColor: '#16a34a',
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: `Gagal (HTTP ${data.status || '?'})`,
                            text: data.message,
                            footer: data.body_preview ? `<small style="text-align:left;">${data.body_preview.substring(0, 200)}</small>` : '',
                            confirmButtonColor: '#4f46e5',
                        });
                    }
                } catch (err) {
                    Swal.fire('Error jaringan', err.message, 'error');
                } finally {
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                }
            }, (err) => {
                btn.disabled = false;
                spinner.classList.add('d-none');
                let msg = 'Gagal dapat lokasi.';
                if (err.code === 1) msg = 'Izin lokasi ditolak. Aktifkan GPS / location permission.';
                else if (err.code === 2) msg = 'Lokasi tidak tersedia.';
                else if (err.code === 3) msg = 'Timeout dapat lokasi.';
                Swal.fire('Geolocation error', msg, 'warning');
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0,
            });
        }

        document.getElementById('btnClockIn').addEventListener('click', () => {
            call('/api/attendance/clock-in', document.getElementById('btnClockIn'), document.getElementById('spinIn'));
        });

        document.getElementById('btnClockOut').addEventListener('click', () => {
            call('/api/attendance/clock-out', document.getElementById('btnClockOut'), document.getElementById('spinOut'));
        });
    </script>
</body>
</html>
