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
        input[type="number"] { font-variant-numeric: tabular-nums; }
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
                    Isi koordinat (atau klik "Pakai lokasi saya"), lalu klik Clock In / Clock Out.
                    Default dari <code>/settings</code> sudah ter-prefill di bawah.
                </p>
            </div>
        </div>

        <div class="card soft-card rounded-4 border-0 mb-4">
            <div class="card-body p-4">
                <h2 class="h6 fw-bold mb-3">Koordinat</h2>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Latitude (-90 .. 90)</label>
                        <input type="number" step="any" id="latInput" class="form-control form-control-lg font-monospace"
                            value="{{ old('lat', $defaultLat) }}" placeholder="-6.218656">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Longitude (-180 .. 180)</label>
                        <input type="number" step="any" id="lonInput" class="form-control form-control-lg font-monospace"
                            value="{{ old('lon', $defaultLon) }}" placeholder="106.812785">
                    </div>
                </div>
                <button type="button" id="btnUseLocation" class="btn btn-outline-secondary">
                    <span class="spinner-border spinner-border-sm d-none me-2" id="spinGeo"></span>
                    📍 Pakai lokasi saya saat ini
                </button>
                <div id="geoStatus" class="text-secondary small mt-2"></div>
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
                        <form method="post" action="{{ route('attendance.api.clock-in') }}" id="clockInForm">
                            @csrf
                            <input type="hidden" name="lat">
                            <input type="hidden" name="lon">
                            <button type="submit" id="btnClockIn" class="btn btn-ok btn-lg w-100">
                                <span class="spinner-border spinner-border-sm d-none me-2" id="spinIn"></span>
                                Clock In
                            </button>
                        </form>
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
                        <form method="post" action="{{ route('attendance.api.clock-out') }}" id="clockOutForm">
                            @csrf
                            <input type="hidden" name="lat">
                            <input type="hidden" name="lon">
                            <button type="submit" id="btnClockOut" class="btn btn-warn btn-lg w-100">
                                <span class="spinner-border spinner-border-sm d-none me-2" id="spinOut"></span>
                                Clock Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const flashResult = @json(session('result'));
        const geoStatus = document.getElementById('geoStatus');

        function readCoords() {
            const lat = parseFloat(document.getElementById('latInput').value);
            const lon = parseFloat(document.getElementById('lonInput').value);
            return { lat, lon, ok: Number.isFinite(lat) && Number.isFinite(lon) };
        }

        async function submitAttendance(event, form, actionLabel, confirmColor) {
            event.preventDefault();

            const { lat, lon, ok } = readCoords();
            if (! ok) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Koordinat belum diisi',
                    text: 'Isi lat/lon dulu atau klik "Pakai lokasi saya".',
                });
                return;
            }

            form.querySelector('input[name="lat"]').value = lat;
            form.querySelector('input[name="lon"]').value = lon;

            const result = await Swal.fire({
                icon: 'question',
                title: `Jalankan ${actionLabel}?`,
                html: `
                    <div class="text-start">
                        <div class="mb-3">Aplikasi akan meneruskan absensi ke Quadrang memakai Cookie dan X-CSRFToken dari /settings.</div>
                        <div class="p-3 rounded-3 bg-light border">
                            <div><strong>Latitude:</strong> ${lat.toFixed(6)}</div>
                            <div><strong>Longitude:</strong> ${lon.toFixed(6)}</div>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Ya, jalankan',
                cancelButtonText: 'Batal',
                confirmButtonColor: confirmColor,
                cancelButtonColor: '#64748b',
                reverseButtons: true,
                focusCancel: true,
            });

            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Memproses...',
                    text: 'Mohon tunggu, sedang submit absensi ke Quadrang.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => Swal.showLoading(),
                });

                form.submit();
            }
        }

        document.getElementById('btnUseLocation').addEventListener('click', () => {
            if (!navigator.geolocation) {
                Swal.fire('Error', 'Browser tidak mendukung geolocation.', 'error');
                return;
            }

            const btn = document.getElementById('btnUseLocation');
            const spin = document.getElementById('spinGeo');
            btn.disabled = true;
            spin.classList.remove('d-none');
            geoStatus.textContent = 'Mengambil lokasi...';

            navigator.geolocation.getCurrentPosition((pos) => {
                document.getElementById('latInput').value = pos.coords.latitude;
                document.getElementById('lonInput').value = pos.coords.longitude;
                geoStatus.innerHTML = `Lokasi dimuat: <code>${pos.coords.latitude.toFixed(6)}, ${pos.coords.longitude.toFixed(6)}</code> &plusmn; ${Math.round(pos.coords.accuracy)} m`;
                btn.disabled = false;
                spin.classList.add('d-none');
            }, (err) => {
                btn.disabled = false;
                spin.classList.add('d-none');
                let msg = 'Gagal dapat lokasi.';
                if (err.code === 1) msg = 'Izin lokasi ditolak. Aktifkan GPS / location permission.';
                else if (err.code === 2) msg = 'Lokasi tidak tersedia.';
                else if (err.code === 3) msg = 'Timeout dapat lokasi.';
                geoStatus.textContent = msg;
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0,
            });
        });

        document.getElementById('clockInForm').addEventListener('submit', (event) => {
            submitAttendance(event, event.currentTarget, 'Clock In', '#16a34a');
        });

        document.getElementById('clockOutForm').addEventListener('submit', (event) => {
            submitAttendance(event, event.currentTarget, 'Clock Out', '#d97706');
        });

        if (flashResult) {
            const footer = flashResult.success && flashResult.lat !== undefined && flashResult.lon !== undefined
                ? `<small>lat=${(+flashResult.lat).toFixed(6)}, lon=${(+flashResult.lon).toFixed(6)}</small>`
                : (flashResult.body_preview ? `<small style="text-align:left;">${flashResult.body_preview.substring(0, 200)}</small>` : '');

            Swal.fire({
                icon: flashResult.success ? 'success' : 'error',
                title: flashResult.success ? 'Absensi Diproses' : `Gagal${flashResult.status ? ` (HTTP ${flashResult.status})` : ''}`,
                text: flashResult.message,
                footer,
                confirmButtonColor: '#4f46e5',
            });
        }
    </script>
</body>
</html>
