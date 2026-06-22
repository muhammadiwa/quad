<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quadrang Timesheet Helper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --ink: #111827;
            --muted: #64748b;
            --line: rgba(15, 23, 42, .08);
            --brand: #4f46e5;
            --brand-dark: #3730a3;
            --surface: rgba(255, 255, 255, .82);
        }

        body {
            min-height: 100vh;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(79, 70, 229, .18), transparent 34rem),
                radial-gradient(circle at top right, rgba(14, 165, 233, .16), transparent 30rem),
                linear-gradient(180deg, #f8fafc 0%, #eef2ff 44%, #f8fafc 100%);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .app-shell {
            max-width: 1180px;
        }

        .hero-card {
            background:
                linear-gradient(135deg, rgba(15, 23, 42, .96), rgba(49, 46, 129, .94)),
                radial-gradient(circle at 85% 10%, rgba(125, 211, 252, .24), transparent 16rem);
            color: #fff;
            border: 0;
            overflow: hidden;
            box-shadow: 0 24px 70px rgba(30, 41, 59, .24);
        }

        .hero-card::after {
            position: absolute;
            right: -80px;
            top: -90px;
            width: 260px;
            height: 260px;
            content: '';
            background: rgba(255, 255, 255, .08);
            border-radius: 50%;
        }

        .hero-eyebrow {
            width: fit-content;
            border: 1px solid rgba(255, 255, 255, .16);
            background: rgba(255, 255, 255, .08);
            backdrop-filter: blur(12px);
        }

        .status-dot {
            width: .65rem;
            height: .65rem;
            display: inline-block;
            border-radius: 999px;
            background: #22c55e;
            box-shadow: 0 0 0 6px rgba(34, 197, 94, .16);
        }

        .soft-card {
            border: 1px solid var(--line) !important;
            background: var(--surface);
            box-shadow: 0 18px 50px rgba(15, 23, 42, .08);
            backdrop-filter: blur(14px);
        }

        .section-title {
            letter-spacing: -.03em;
        }

        .form-control,
        .form-check-input {
            border-color: #dbe3ef;
        }

        .form-control:focus,
        .form-check-input:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 .25rem rgba(79, 70, 229, .13);
        }

        .form-check-input:checked {
            background-color: var(--brand);
            border-color: var(--brand);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--brand), #2563eb);
            border: 0;
            box-shadow: 0 14px 28px rgba(79, 70, 229, .24);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--brand-dark), #1d4ed8);
            transform: translateY(-1px);
        }

        .step-number {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #eef2ff;
            color: #4338ca;
            font-weight: 700;
        }

        .info-tile {
            border: 1px solid #e6edf7;
            background: linear-gradient(180deg, #fff, #f8fafc);
        }

        .mini-icon {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: #eef2ff;
            color: var(--brand);
            font-weight: 800;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: .6rem;
        }

        .calendar-panel {
            border: 1px solid #e2e8f0;
            border-radius: 28px;
            background: linear-gradient(180deg, rgba(255, 255, 255, .96), rgba(248, 250, 252, .9));
            padding: 1rem;
        }

        .calendar-month-badge {
            border: 1px solid #dbeafe;
            background: linear-gradient(135deg, #eef2ff, #eff6ff);
            color: #312e81;
        }

        .calendar-summary-card {
            border: 1px solid #e2e8f0;
            background: #fff;
            border-radius: 18px;
            padding: .85rem 1rem;
        }

        .calendar-summary-value {
            font-size: 1.25rem;
            line-height: 1;
            font-weight: 800;
            letter-spacing: -.03em;
        }

        .calendar-day {
            min-height: 92px;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            background: #fff;
            padding: .7rem;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .04);
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .calendar-day:not(.is-muted):hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 34px rgba(15, 23, 42, .09);
        }

        .calendar-day.is-muted {
            opacity: .32;
            box-shadow: none;
        }

        .calendar-day.is-workday {
            border-color: #c7d2fe;
            background: linear-gradient(180deg, #eef2ff, #fff);
        }

        .calendar-day.is-weekend {
            border-color: #fecaca;
            background: linear-gradient(180deg, #fff1f2, #fff);
        }

        .calendar-day.is-holiday {
            border-color: #fde68a;
            background: linear-gradient(180deg, #fffbeb, #fff);
        }

        .calendar-date {
            font-weight: 700;
            font-size: 1.05rem;
        }

        .calendar-label {
            max-width: 100%;
            white-space: normal;
            text-align: left;
            font-size: .7rem;
            line-height: 1.2;
        }

        .result-pill {
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #334155;
        }

        .weekday-header {
            padding: .5rem 0;
            border-radius: 12px;
            background: rgba(255, 255, 255, .65);
        }

        .legend-dot {
            width: .65rem;
            height: .65rem;
            display: inline-block;
            border-radius: 999px;
        }

        @media (max-width: 575.98px) {
            .calendar-grid {
                gap: .35rem;
            }

            .calendar-day {
                min-height: 72px;
                border-radius: 14px;
                padding: .45rem;
            }

            .calendar-label {
                display: none;
            }
        }
    </style>
</head>
<body>
    <main class="container app-shell py-5">
        <div class="d-flex justify-content-end mb-3 gap-2">
            <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                🕒 Attendance
            </a>
            <a href="{{ route('settings.edit') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                ⚙ Settings
            </a>
        </div>
        <div class="card hero-card position-relative rounded-5 mb-4">
            <div class="card-body p-4 p-lg-5 position-relative">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <div class="hero-eyebrow d-flex align-items-center gap-2 mb-3 text-white-50 rounded-pill px-3 py-2">
                            <span class="status-dot"></span>
                            <span class="small fw-semibold">Quadrang automation</span>
                        </div>
                        <h1 class="display-5 fw-bold mb-3">Timesheet Helper</h1>
                        <p class="lead mb-0 text-white-50">
                            Pilih range tanggal, cek preview kalender, lalu buat task Quadrang satu per satu secara otomatis untuk hari kerja yang valid.
                        </p>
                    </div>
                    <div class="col-lg-4">
                        <div class="bg-white bg-opacity-10 rounded-4 p-4 border border-white border-opacity-10">
                            <div class="small text-white-50 mb-2">Filter aktif</div>
                            <div class="d-flex flex-column gap-2">
                                <div class="fw-semibold">Weekend otomatis dilewati</div>
                                <div class="fw-semibold">Public holiday Indonesia dicocokkan</div>
                                <div class="fw-semibold">Range dibatasi satu bulan</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card soft-card rounded-5 border-0">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex align-items-start gap-3 mb-4">
                            <span class="mini-icon">1</span>
                            <div>
                                <h2 class="h4 section-title fw-bold mb-1">Create Timesheet Task</h2>
                                <p class="text-secondary mb-0">Atur deskripsi dan range tanggal sebelum submit ke Quadrang.</p>
                            </div>
                        </div>

                        <form method="post" action="{{ route('timesheet.store') }}" id="timesheetForm">
                            @csrf
                            <div class="mb-4">
                                <label for="task" class="form-label fw-semibold">Description Task</label>
                                <textarea id="task" name="task" rows="5" class="form-control form-control-lg rounded-4" placeholder="Contoh: Migrasi ESB ke Brigate dan SOAP ke REST API" required>{{ old('task', $defaultTask) }}</textarea>
                                <div class="form-text">Deskripsi ini akan dikirim untuk setiap task harian yang dibuat.</div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-sm-6">
                                    <label for="start_date" class="form-label fw-semibold">Start Date</label>
                                    <input type="date" id="start_date" name="start_date" class="form-control form-control-lg rounded-4" value="{{ old('start_date', $defaultStartDate) }}" required>
                                </div>
                                <div class="col-sm-6">
                                    <label for="end_date" class="form-label fw-semibold">End Date</label>
                                    <input type="date" id="end_date" name="end_date" class="form-control form-control-lg rounded-4" value="{{ old('end_date', $defaultEndDate) }}" required>
                                </div>
                            </div>

                            <div class="form-check form-switch mb-4 p-3 rounded-4 info-tile ps-5">
                                <input class="form-check-input" type="checkbox" role="switch" id="skip_holidays" name="skip_holidays" value="1" @checked(old('skip_holidays', '1'))>
                                <label class="form-check-label fw-semibold" for="skip_holidays">Skip public holiday Indonesia</label>
                                <div class="form-text">Data holiday dan cuti bersama diambil dari libur.deno.dev untuk preview/filter otomatis.</div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-sm-6">
                                    <div class="p-3 rounded-4 info-tile h-100">
                                        <div class="small text-secondary">Jam kerja</div>
                                        <div class="fw-semibold">07:30 - 16:30</div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 rounded-4 info-tile h-100">
                                        <div class="small text-secondary">Filter tanggal</div>
                                        <div class="fw-semibold">Skip weekend & holiday</div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid d-sm-flex gap-2 align-items-center">
                                <button type="submit" class="btn btn-primary btn-lg rounded-4 px-4">
                                    Jalankan Create Timesheet
                                </button>
                                <span class="small text-secondary">Range harus dalam bulan yang sama.</span>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card soft-card rounded-5 border-0 mt-4">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                            <div>
                                <h2 class="h4 section-title fw-bold mb-1">Preview Kalender</h2>
                                <p class="text-secondary mb-0">Cek tanggal yang akan dibuat sebelum submit ke Quadrang.</p>
                            </div>
                            <div class="badge rounded-pill text-bg-light border px-3 py-2" id="holidayStatus">Memuat holiday...</div>
                        </div>

                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                            <div class="calendar-month-badge rounded-4 px-3 py-2">
                                <div class="small text-uppercase fw-semibold opacity-75">Bulan Preview</div>
                                <div class="h5 fw-bold mb-0" id="calendarMonthTitle">-</div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 small">
                                <span class="badge rounded-pill text-bg-light border"><span class="legend-dot bg-primary me-1"></span>Create</span>
                                <span class="badge rounded-pill text-bg-light border"><span class="legend-dot bg-danger me-1"></span>Weekend</span>
                                <span class="badge rounded-pill text-bg-light border"><span class="legend-dot bg-warning me-1"></span>Holiday</span>
                                <span class="badge rounded-pill text-bg-light border"><span class="legend-dot bg-secondary me-1"></span>Di luar range</span>
                            </div>
                        </div>

                        <div class="row g-2 mb-3" id="calendarSummary">
                            <div class="col-6 col-md-3">
                                <div class="calendar-summary-card">
                                    <div class="calendar-summary-value text-primary" id="summaryCreate">0</div>
                                    <div class="small text-secondary">Create</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="calendar-summary-card">
                                    <div class="calendar-summary-value text-warning" id="summaryHoliday">0</div>
                                    <div class="small text-secondary">Holiday</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="calendar-summary-card">
                                    <div class="calendar-summary-value text-danger" id="summaryWeekend">0</div>
                                    <div class="small text-secondary">Weekend</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="calendar-summary-card">
                                    <div class="calendar-summary-value text-secondary" id="summaryOutRange">0</div>
                                    <div class="small text-secondary">Out range</div>
                                </div>
                            </div>
                        </div>

                        <div class="calendar-panel">
                            <div class="calendar-grid text-center fw-semibold small text-secondary mb-2">
                                <div class="weekday-header">Min</div>
                                <div class="weekday-header">Sen</div>
                                <div class="weekday-header">Sel</div>
                                <div class="weekday-header">Rab</div>
                                <div class="weekday-header">Kam</div>
                                <div class="weekday-header">Jum</div>
                                <div class="weekday-header">Sab</div>
                            </div>
                            <div class="calendar-grid" id="calendarGrid"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card soft-card rounded-5 border-0 mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 section-title fw-bold mb-3">Cara Kerja</h2>
                        <div class="d-flex gap-3 mb-3">
                            <span class="step-number">1</span>
                            <div>
                                <div class="fw-semibold">Create timesheet bulan berjalan</div>
                                <div class="text-secondary small">Aplikasi memanggil endpoint create milik Quadrang.</div>
                            </div>
                        </div>
                        <div class="d-flex gap-3 mb-3">
                            <span class="step-number">2</span>
                            <div>
                                <div class="fw-semibold">Ambil Timesheet ID</div>
                                <div class="text-secondary small">ID dibaca dari halaman timesheet Quadrang.</div>
                            </div>
                        </div>
                        <div class="d-flex gap-3">
                            <span class="step-number">3</span>
                            <div>
                                <div class="fw-semibold">Submit task satu per satu</div>
                                <div class="text-secondary small">Quadrang memang input task per tanggal, jadi aplikasi melakukan loop otomatis sesuai range.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-warning-subtle rounded-5 soft-card">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold text-warning-emphasis mb-3">Yang Masih Belum Dicek</h2>
                        <ul class="mb-0 text-secondary">
                            <li>Task yang sudah ada pada tanggal yang sama.</li>
                            <li>Detail sukses/gagal untuk setiap tanggal.</li>
                            <li>Range lintas bulan belum didukung agar tidak salah timesheet ID.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const flashResult = @json(session('result'));
        const validationErrors = @json($errors->all());
        const startInput = document.getElementById('start_date');
        const endInput = document.getElementById('end_date');
        const skipHolidayInput = document.getElementById('skip_holidays');
        const timesheetForm = document.getElementById('timesheetForm');
        const calendarGrid = document.getElementById('calendarGrid');
        const holidayStatus = document.getElementById('holidayStatus');
        const calendarMonthTitle = document.getElementById('calendarMonthTitle');
        const summaryCreate = document.getElementById('summaryCreate');
        const summaryHoliday = document.getElementById('summaryHoliday');
        const summaryWeekend = document.getElementById('summaryWeekend');
        const summaryOutRange = document.getElementById('summaryOutRange');
        const holidayCache = new Map();
        const monthFormatter = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' });

        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        }

        function parseDate(value) {
            return new Date(`${value}T00:00:00`);
        }

        async function getHolidays(year, month) {
            if (!skipHolidayInput.checked) {
                return new Map();
            }

            const cacheKey = `${year}-${month}`;

            if (holidayCache.has(cacheKey)) {
                return holidayCache.get(cacheKey);
            }

            holidayStatus.textContent = 'Memuat public holiday...';

            try {
                const response = await fetch(`{{ route('timesheet.holidays') }}?year=${year}&month=${month}`, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });
                const data = await response.json();
                const holidays = new Map(
                    data.data.map((holiday) => [holiday.date, holiday.name])
                );

                holidayCache.set(cacheKey, holidays);
                holidayStatus.textContent = `${holidays.size} public holiday dimuat`;

                return holidays;
            } catch (error) {
                holidayStatus.textContent = 'Gagal memuat holiday, preview tanpa holiday';

                return new Map();
            }
        }

        async function renderCalendar() {
            if (!startInput.value || !endInput.value) {
                return;
            }

            const start = parseDate(startInput.value);
            const end = parseDate(endInput.value);

            if (start > end) {
                calendarGrid.innerHTML = '<div class="alert alert-danger grid-column-1">Start date tidak boleh lebih besar dari end date.</div>';
                return;
            }

            if (start.getMonth() !== end.getMonth() || start.getFullYear() !== end.getFullYear()) {
                calendarGrid.innerHTML = '<div class="alert alert-warning grid-column-1">Range harus berada pada bulan yang sama.</div>';
                return;
            }

            const holidays = await getHolidays(start.getFullYear(), start.getMonth() + 1);
            const monthStart = new Date(start.getFullYear(), start.getMonth(), 1);
            const monthEnd = new Date(start.getFullYear(), start.getMonth() + 1, 0);
            const cells = [];
            const summary = {
                create: 0,
                holiday: 0,
                weekend: 0,
                outRange: 0,
            };

            calendarMonthTitle.textContent = monthFormatter.format(monthStart);

            for (let i = 0; i < monthStart.getDay(); i++) {
                cells.push('<div class="calendar-day is-muted"></div>');
            }

            for (let day = 1; day <= monthEnd.getDate(); day++) {
                const date = new Date(start.getFullYear(), start.getMonth(), day);
                const key = formatDate(date);
                const isInRange = date >= start && date <= end;
                const isWeekend = date.getDay() === 0 || date.getDay() === 6;
                const holidayName = holidays.get(key);
                const isHoliday = Boolean(holidayName) && skipHolidayInput.checked;
                let className = 'calendar-day';
                let label = 'Create';
                let badge = 'text-bg-primary';

                if (!isInRange) {
                    className += ' is-muted';
                    label = 'Di luar range';
                    badge = 'text-bg-secondary';
                    summary.outRange++;
                } else if (isHoliday && isWeekend) {
                    className += ' is-holiday';
                    label = `${holidayName} / Weekend`;
                    badge = 'text-bg-warning';
                    summary.holiday++;
                } else if (isHoliday) {
                    className += ' is-holiday';
                    label = holidayName;
                    badge = 'text-bg-warning';
                    summary.holiday++;
                } else if (isWeekend) {
                    className += ' is-weekend';
                    label = 'Weekend';
                    badge = 'text-bg-danger';
                    summary.weekend++;
                } else {
                    className += ' is-workday';
                    summary.create++;
                }

                cells.push(`
                    <div class="${className}">
                        <div class="calendar-date">${day}</div>
                        <span class="badge ${badge} calendar-label mt-2">${label}</span>
                    </div>
                `);
            }

            calendarGrid.innerHTML = cells.join('');
            summaryCreate.textContent = summary.create;
            summaryHoliday.textContent = summary.holiday;
            summaryWeekend.textContent = summary.weekend;
            summaryOutRange.textContent = summary.outRange;
        }

        startInput.addEventListener('change', renderCalendar);
        endInput.addEventListener('change', renderCalendar);
        skipHolidayInput.addEventListener('change', renderCalendar);

        timesheetForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const result = await Swal.fire({
                icon: 'question',
                title: 'Jalankan Create Timesheet?',
                html: `
                    <div class="text-start">
                        <div class="mb-3">Aplikasi akan submit task satu per satu ke Quadrang berdasarkan range yang dipilih.</div>
                        <div class="p-3 rounded-3 bg-light border">
                            <div><strong>Start:</strong> ${startInput.value}</div>
                            <div><strong>End:</strong> ${endInput.value}</div>
                            <div><strong>Skip holiday:</strong> ${skipHolidayInput.checked ? 'Ya' : 'Tidak'}</div>
                        </div>
                        <div class="small text-secondary mt-3">Pastikan preview kalender sudah benar sebelum melanjutkan.</div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Ya, jalankan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
                focusCancel: true,
            });

            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Memproses...',
                    text: 'Mohon tunggu, sedang submit task ke Quadrang.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => Swal.showLoading(),
                });

                timesheetForm.submit();
            }
        });

        if (flashResult) {
            const data = flashResult.data || {};
            const created = Array.isArray(data.created) ? data.created.length : 0;
            const skippedWeekend = Array.isArray(data.skipped_weekend) ? data.skipped_weekend.length : 0;
            const skippedHoliday = Array.isArray(data.skipped_holiday) ? data.skipped_holiday.length : 0;
            const failed = Array.isArray(data.failed) ? data.failed.length : 0;
            const detailHtml = flashResult.success
                ? `
                    <div class="text-start">
                        <div class="row g-2 mt-2">
                            <div class="col-6"><div class="p-3 rounded-3 bg-primary-subtle"><strong>${created}</strong><br><span class="small">Created</span></div></div>
                            <div class="col-6"><div class="p-3 rounded-3 bg-danger-subtle"><strong>${failed}</strong><br><span class="small">Failed</span></div></div>
                            <div class="col-6"><div class="p-3 rounded-3 bg-warning-subtle"><strong>${skippedHoliday}</strong><br><span class="small">Holiday</span></div></div>
                            <div class="col-6"><div class="p-3 rounded-3 bg-secondary-subtle"><strong>${skippedWeekend}</strong><br><span class="small">Weekend</span></div></div>
                        </div>
                    </div>
                `
                : '';

            Swal.fire({
                icon: flashResult.success ? 'success' : 'error',
                title: flashResult.success ? 'Timesheet Diproses' : 'Gagal Memproses',
                text: flashResult.message,
                html: detailHtml || undefined,
                confirmButtonText: 'OK',
                confirmButtonColor: '#4f46e5',
                width: 560,
            });
        }

        if (validationErrors.length > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Form Belum Valid',
                html: `<div class="text-start">${validationErrors.map((error) => `<div class="mb-1">${error}</div>`).join('')}</div>`,
                confirmButtonText: 'Perbaiki',
                confirmButtonColor: '#4f46e5',
            });
        }

        renderCalendar();
    </script>
</body>
</html>
