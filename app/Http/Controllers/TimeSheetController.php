<?php

namespace App\Http\Controllers;

use App\Services\QuadrangService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimeSheetController extends Controller
{
    public function __construct(protected QuadrangService $service) {}

    public function index()
    {
        $now = now();

        return view('timesheet.create', [
            'defaultTask' => 'Migrasi ESB ke Brigate dan SOAP ke REST API',
            'defaultStartDate' => $now->copy()->startOfMonth()->toDateString(),
            'defaultEndDate' => $now->copy()->endOfMonth()->toDateString(),
        ]);
    }

    /**
     * @throws ConnectionException
     */
    public function store(Request $request)
    {
        set_time_limit(300);

        $validated = $request->validate([
            'task' => ['required', 'string', 'max:1000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'skip_holidays' => ['nullable', 'boolean'],
        ]);

        $start = \Carbon\Carbon::parse($validated['start_date']);
        $end = \Carbon\Carbon::parse($validated['end_date']);

        if (! $start->isSameMonth($end)) {
            return back()->withErrors([
                'end_date' => 'Date range harus berada pada bulan yang sama karena Quadrang timesheet diproses per bulan.',
            ])->withInput();
        }

        $this->service->setMonthYearFromDate($validated['start_date']);

        $created = $this->service->createTimeSheet();
        $response = $this->service->getCurrentTimeSheet();
        preg_match_all(
            '#href="/attendance/task/(\d+)"#',
            $response->body(),
            $matches
        );

        $ids = array_map('strval', $matches[1]);

        if (! empty($ids)) {
            $result = $this->service->createTask(
                $ids[0],
                $validated['task'],
                $validated['start_date'],
                $validated['end_date'],
                $request->boolean('skip_holidays', true)
            );

            return back()->with('result', [
                'success' => true,
                'message' => 'Task timesheet berhasil diproses.',
                'data' => $result,
            ])->withInput();
        }

        return back()->with('result', [
            'success' => false,
            'message' => 'Timesheet ID tidak ditemukan dari halaman Quadrang.',
            'data' => [
                'created_timesheet' => $created,
            ],
        ])->withInput();
    }

    public function holidays(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $holidays = $this->service->getIndonesianHolidays(
            (int) $validated['year'],
            isset($validated['month']) ? (int) $validated['month'] : null
        );

        return response()->json([
            'success' => true,
            'data' => collect($holidays)->map(fn (string $name, string $date) => [
                'date' => $date,
                'name' => $name,
            ])->values(),
        ]);
    }

    /**
     * @throws ConnectionException
     */
    public function create(Request $request): JsonResponse
    {
        set_time_limit(300);

        $validated = $request->validate([
            'task' => ['nullable', 'string', 'max:1000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'skip_holidays' => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['start_date']) && ! empty($validated['end_date'])) {
            $start = \Carbon\Carbon::parse($validated['start_date']);
            $end = \Carbon\Carbon::parse($validated['end_date']);

            if (! $start->isSameMonth($end)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Date range must be in the same month',
                ], 422);
            }

            $this->service->setMonthYearFromDate($validated['start_date']);
        }

        $task = $validated['task'] ?? 'Migrasi ESB ke Brigate dan SOAP ke REST API';

        $created = $this->service->createTimeSheet();
        $response = $this->service->getCurrentTimeSheet();
        preg_match_all(
            '#href="/attendance/task/(\d+)"#',
            $response->body(),
            $matches
        );

        $ids = array_map('strval', $matches[1]);

        if (! empty($ids)) {
            $result = $this->service->createTask(
                $ids[0],
                $task,
                $validated['start_date'] ?? null,
                $validated['end_date'] ?? null,
                $request->boolean('skip_holidays', true)
            );

            return response()->json([
                'success' => true,
                'message' => 'Task created successfully',
                'data' => $result,
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => 'Timesheet ID not found',
            'error' => [
                'created_timesheet' => $created,
            ],
        ], 404);
    }
}
