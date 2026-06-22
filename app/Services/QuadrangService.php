<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class QuadrangService
{
    public string $year;

    public string $month;

    public function __construct()
    {
        $now = Carbon::now();
        $this->year = $now->year;
        $this->month = $now->month;
    }

    /**
     * @throws ConnectionException
     */
    public function createTimeSheet(): bool
    {
        $payload = [
            'id_user' => '',
            'year' => $this->year,
            'month' => $this->month,
        ];

        $response = Http::timeout(20)->connectTimeout(15)->retry(3, 2000, throw: false)->asForm()->withHeaders([
            'X-CSRFToken' => config('quadrang.config.csrf_token'),
            'Referer' => 'https://quadrang.steradian.co.id/attendance/timesheet-view',
            'Cookie' => config('quadrang.config.cookie'),
        ])->post('https://quadrang.steradian.co.id/attendance/timesheet-create', $payload);

        if ($response->body() !== '<script>window.location.reload()</script>' || $response->failed()) {
            return false;
        }

        return true;
    }

    /**
     * @throws ConnectionException
     */
    public function getCurrentTimeSheet(): Response
    {
        return Http::timeout(20)->connectTimeout(15)->retry(3, 2000, throw: false)->withHeaders([
            'Cookie' => config('quadrang.config.cookie'),
        ])->get("https://quadrang.steradian.co.id/attendance/timesheet-view/?month=$this->month&year=$this->year");
    }

    /**
     * @throws ConnectionException
     */
    public function createTask(string $timeSheetId, string $task, ?string $startDate = null, ?string $endDate = null, bool $skipHolidays = true): array
    {
        $start = $startDate ? Carbon::parse($startDate) : Carbon::createFromDate($this->year, $this->month, 1);
        $end = $endDate ? Carbon::parse($endDate) : $start->copy()->endOfMonth();

        $this->year = (string) $start->year;
        $this->month = (string) $start->month;

        $period = CarbonPeriod::create($start, $end);
        $holidays = $skipHolidays ? $this->getIndonesianHolidays((int) $this->year, (int) $this->month) : [];

        $basePayload = [
            [
                'name' => 'type_task',
                'contents' => 'Work',
            ],
            [
                'name' => 'id_project',
                'contents' => '17',
            ],
            [
                'name' => 'start_at',
                'contents' => '07:30',
            ],
            [
                'name' => 'end_at',
                'contents' => '16:30',
            ],
            [
                'name' => 'description',
                'contents' => $task,
            ],
            [
                'name' => 'location',
                'contents' => 'On Site',
            ],
            [
                'name' => 'skills',
                'contents' => '70',
            ],
            [
                'name' => 'custom_location',
                'contents' => null,
            ],
        ];

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
                [
                    'name' => 'date',
                    'contents' => (int) $date->format('d'),
                ],
                [
                    'name' => 'end_date',
                    'contents' => (int) $date->format('d'),
                ],
            ]);

            try {
                $response = Http::timeout(20)->connectTimeout(15)->retry(3, 2000, throw: false)->asMultipart()
                    ->withHeaders([
                        'X-CSRFToken' => config('quadrang.config.csrf_token'),
                        'Referer' => "https://quadrang.steradian.co.id/attendance/task/$timeSheetId",
                        'Cookie' => config('quadrang.config.cookie'),
                    ])->post("https://quadrang.steradian.co.id/attendance/task-create/$timeSheetId", $multipartPayload);

                if ($response && $response->successful()) {
                    $created[] = $dateKey;

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

    public function getIndonesianHolidays(int $year, ?int $month = null): array
    {
        $params = $year === (int) now()->year ? [] : ['year' => $year];

        $response = Http::timeout(15)->connectTimeout(10)->retry(3, 2000, throw: false)->get('https://libur.deno.dev/api', $params);

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
