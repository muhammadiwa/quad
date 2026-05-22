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

        $response = Http::asForm()->withHeaders([
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
        return Http::withHeaders([
            'Cookie' => config('quadrang.config.cookie'),
        ])->get("https://quadrang.steradian.co.id/attendance/timesheet-view/?month=$this->month&year=$this->year");
    }

    /**
     * @throws ConnectionException
     */
    public function createTask(string $timeSheetId, string $task): array
    {
        $year = $this->year;
        $month = $this->month;

        $start = Carbon::createFromDate($year, $month, 1);
        $end = $start->copy()->endOfMonth();

        $period = CarbonPeriod::create($start, $end);

        $multipartPayload = [
            [
                'name' => 'type_task',
                'contents' => 'Work',
            ],
            [
                'name' => 'id_project',
                'contents' => '40',
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
                'contents' => '80',
            ],
            [
                'name' => 'custom_location',
                'contents' => null,
            ],
        ];

        foreach ($period as $date) {
            if (! $date->isWeekend()) {
                $multipartPayload[] = [
                    'name' => 'date',
                    'contents' => (int) $date->format('d'),
                ];
                $multipartPayload[] = [
                    'name' => 'end_date',
                    'contents' => (int) $date->format('d'),
                ];

                Http::asMultipart()
                    ->withHeaders([
                        'X-CSRFToken' => config('quadrang.config.csrf_token'),
                        'Referer' => "https://quadrang.steradian.co.id/attendance/task/$timeSheetId",
                        'Cookie' => config('quadrang.config.cookie'),
                    ])->post("https://quadrang.steradian.co.id/attendance/task-create/$timeSheetId", $multipartPayload);
            }
        }

        return [
            'timesheet_id' => $timeSheetId,
            'month' => $month,
            'year' => $year,
        ];
    }
}
