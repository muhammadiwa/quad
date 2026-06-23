<?php

namespace App\Services;

use App\Models\QuadrangSetting;
use App\Models\TaskTemplate;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class QuadrangService
{
    public string $year;

    public string $month;

    private const REQUEST_TIMEOUT = 20;

    private const CONNECT_TIMEOUT = 15;

    private const RETRY_TIMES = 3;

    private const RETRY_SLEEP_MS = 2000;

    public function __construct()
    {
        $now = Carbon::now();
        $this->year = (string) $now->year;
        $this->month = (string) $now->month;
    }

    public function baseUrl(): string
    {
        return QuadrangSetting::get('base_url', 'https://quadrang.steradian.co.id');
    }

    public function cookie(): string
    {
        return QuadrangSetting::get('cookie', '');
    }

    public function csrfToken(): string
    {
        return QuadrangSetting::get('csrf_token', '');
    }

    public function userAgent(): string
    {
        return QuadrangSetting::get('user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    }

    private function commonHeaders(): array
    {
        return [
            'User-Agent' => $this->userAgent(),
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cookie' => $this->cookie(),
        ];
    }

    /**
     * @throws ConnectionException
     */
    public function createTimeSheet(): bool
    {
        $response = Http::timeout(self::REQUEST_TIMEOUT)
            ->connectTimeout(self::CONNECT_TIMEOUT)
            ->retry(self::RETRY_TIMES, self::RETRY_SLEEP_MS, throw: false)
            ->withHeaders(array_merge($this->commonHeaders(), [
                'X-CSRFToken' => $this->csrfToken(),
                'Referer' => $this->baseUrl() . '/attendance/timesheet-view',
            ]))
            ->asForm()
            ->post($this->baseUrl() . '/attendance/timesheet-create', [
                'id_user' => '',
                'year' => $this->year,
                'month' => $this->month,
            ]);

        $body = $response->body();
        $scriptOk = $body === '<script>window.location.reload()</script>';
        $alreadyExists = str_contains($body, 'sudah ada') || str_contains($body, 'already exists');

        \Log::info('quadrang.createTimeSheet', [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'script_ok' => $scriptOk,
            'already_exists' => $alreadyExists,
            'body_length' => strlen($body),
            'body_first_300' => substr(preg_replace('/\s+/', ' ', strip_tags($body)), 0, 300),
        ]);

        if (! $response->successful()) {
            return false;
        }

        return $scriptOk || $alreadyExists;
    }

    /**
     * @throws ConnectionException
     */
    public function getCurrentTimeSheet(): Response
    {
        return Http::timeout(self::REQUEST_TIMEOUT)
            ->connectTimeout(self::CONNECT_TIMEOUT)
            ->retry(self::RETRY_TIMES, self::RETRY_SLEEP_MS, throw: false)
            ->withHeaders($this->commonHeaders())
            ->get($this->baseUrl() . "/attendance/timesheet-view/?month={$this->month}&year={$this->year}");
    }

    public function findCurrentTimesheetId(string $body): ?string
    {
        $monthName = Carbon::createFromDate((int) $this->year, (int) $this->month, 1)
            ->locale('id')->monthName;
        $year = (string) $this->year;

        $pattern = '#<div class="oh-sticky-table__tr">(.*?)<a href="/attendance/task/(\d+)"#s';

        if (preg_match_all($pattern, $body, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                if (str_contains($m[1], ">$monthName<") && str_contains($m[1], ">$year<")) {
                    return $m[2];
                }
            }
        }

        return null;
    }

    /**
     * @throws ConnectionException
     */
    public function createTask(
        string $timeSheetId,
        string $task,
        ?string $startDate = null,
        ?string $endDate = null,
        bool $skipHolidays = true,
        ?TaskTemplate $template = null,
        ?\Closure $onProgress = null,
    ): array {
        $template ??= TaskTemplate::default();

        $start = $startDate ? Carbon::parse($startDate) : Carbon::createFromDate($this->year, $this->month, 1);
        $end = $endDate ? Carbon::parse($endDate) : $start->copy()->endOfMonth();

        $this->year = (string) $start->year;
        $this->month = (string) $start->month;

        $period = CarbonPeriod::create($start, $end);
        $holidays = $skipHolidays ? $this->getIndonesianHolidays((int) $this->year, (int) $this->month) : [];

        $basePayload = $template
            ? $template->toMultipartBase()
            : [
                ['name' => 'type_task', 'contents' => 'Work'],
                ['name' => 'id_project', 'contents' => '17'],
                ['name' => 'start_at', 'contents' => '07:30'],
                ['name' => 'end_at', 'contents' => '16:30'],
                ['name' => 'location', 'contents' => 'On Site'],
                ['name' => 'skills', 'contents' => '70'],
                ['name' => 'custom_location', 'contents' => null],
            ];
        $basePayload[] = ['name' => 'description', 'contents' => $task];

        $created = [];
        $failed = [];
        $skippedWeekend = [];
        $skippedHoliday = [];

        foreach ($period as $date) {
            $dateKey = $date->toDateString();

            if ($date->isWeekend()) {
                $skippedWeekend[] = $dateKey;

                continue;
            }

            if (isset($holidays[$dateKey])) {
                $skippedHoliday[] = [
                    'date' => $dateKey,
                    'name' => $holidays[$dateKey],
                ];

                continue;
            }

            $multipartPayload = array_merge($basePayload, [
                ['name' => 'date', 'contents' => (int) $date->format('d')],
                ['name' => 'end_date', 'contents' => (int) $date->format('d')],
            ]);

            try {
                $response = Http::timeout(self::REQUEST_TIMEOUT)
                    ->connectTimeout(self::CONNECT_TIMEOUT)
                    ->retry(self::RETRY_TIMES, self::RETRY_SLEEP_MS, throw: false)
                    ->asMultipart()
                    ->withHeaders(array_merge($this->commonHeaders(), [
                        'X-CSRFToken' => $this->csrfToken(),
                        'Referer' => $this->baseUrl() . "/attendance/task/{$timeSheetId}",
                    ]))
                    ->post($this->baseUrl() . "/attendance/task-create/{$timeSheetId}", $multipartPayload);

                if ($response && $response->successful()) {
                    $created[] = $dateKey;

                    if ($onProgress) {
                        $onProgress([
                            'created' => count($created),
                            'failed' => count($failed),
                            'last_date' => $dateKey,
                        ]);
                    }

                    usleep(300000);

                    continue;
                }

                $failed[] = [
                    'date' => $dateKey,
                    'status' => $response?->status() ?? 0,
                ];
            } catch (\Throwable $e) {
                $failed[] = [
                    'date' => $dateKey,
                    'status' => 'exception: ' . $e->getMessage(),
                ];
            }

            usleep(300000);
        }

        return [
            'timesheet_id' => $timeSheetId,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'month' => $this->month,
            'year' => $this->year,
            'created' => $created,
            'skipped_weekend' => $skippedWeekend,
            'skipped_holiday' => $skippedHoliday,
            'failed' => $failed,
        ];
    }

    public function setMonthYearFromDate(string $date): void
    {
        $parsed = Carbon::parse($date);
        $this->year = (string) $parsed->year;
        $this->month = (string) $parsed->month;
    }

    public function clockIn(float $lat, float $lon): array
    {
        $response = Http::timeout(self::REQUEST_TIMEOUT)
            ->connectTimeout(self::CONNECT_TIMEOUT)
            ->retry(self::RETRY_TIMES, self::RETRY_SLEEP_MS, throw: false)
            ->withHeaders(array_merge($this->commonHeaders(), [
                'X-CSRFToken' => $this->csrfToken(),
                'Referer' => $this->baseUrl() . '/attendance',
            ]))
            ->get($this->baseUrl() . '/attendance/clock-in', [
                'lat' => $lat,
                'lon' => $lon,
            ]);

        return $this->summarizeAttendanceResponse('clockIn', $response);
    }

    public function clockOut(float $lat, float $lon): array
    {
        $response = Http::timeout(self::REQUEST_TIMEOUT)
            ->connectTimeout(self::CONNECT_TIMEOUT)
            ->retry(self::RETRY_TIMES, self::RETRY_SLEEP_MS, throw: false)
            ->withHeaders(array_merge($this->commonHeaders(), [
                'X-CSRFToken' => $this->csrfToken(),
                'Referer' => $this->baseUrl() . '/attendance',
            ]))
            ->get($this->baseUrl() . '/attendance/clock-out', [
                'lat' => $lat,
                'lon' => $lon,
            ]);

        return $this->summarizeAttendanceResponse('clockOut', $response);
    }

    private function summarizeAttendanceResponse(string $action, $response): array
    {
        $body = $response->body();
        $visible = trim(preg_replace('/\s+/', ' ', strip_tags($body)));
        $expectedNextAction = $action === 'clockIn' ? 'Check-Out' : 'Check-In';
        $loginPage = str_contains($visible, 'Login - Dashboard QuadraNG')
            || str_contains($visible, 'Secure Sign-in');
        $csrfMismatch = str_contains($visible, 'CSRF token mismatch')
            || str_contains($visible, 'CSRF verification failed');
        $stateUpdated = str_contains($visible, $expectedNextAction);
        $success = $response->successful()
            && $stateUpdated
            && ! $loginPage
            && ! $csrfMismatch;

        \Log::info("quadrang.$action", [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'state_updated' => $stateUpdated,
            'expected_next_action' => $expectedNextAction,
            'login_page' => $loginPage,
            'csrf_mismatch' => $csrfMismatch,
            'body_length' => strlen($body),
            'body_first_300' => substr($visible, 0, 300),
        ]);

        return [
            'success' => $success,
            'status' => $response->status(),
            'body' => $visible,
        ];
    }

    public function getIndonesianHolidays(int $year, ?int $month = null): array
    {
        $params = $year === (int) now()->year ? [] : ['year' => $year];

        $response = Http::timeout(15)
            ->connectTimeout(10)
            ->retry(self::RETRY_TIMES, self::RETRY_SLEEP_MS, throw: false)
            ->get('https://libur.deno.dev/api', $params);

        if ($response->failed()) {
            return [];
        }

        return collect($response->json())
            ->when($month, fn ($holidays) => $holidays->filter(
                fn (array $holiday) => Carbon::parse($holiday['date'])->month === $month
            ))
            ->mapWithKeys(fn (array $holiday) => [
                $holiday['date'] => $holiday['name'] ?? 'Libur nasional',
            ])
            ->all();
    }
}
