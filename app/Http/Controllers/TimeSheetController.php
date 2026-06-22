<?php

namespace App\Http\Controllers;

use App\Models\QuadrangSetting;
use App\Services\QuadrangService;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TimeSheetController extends Controller
{
    public function __construct(protected QuadrangService $service) {}

    public function index()
    {
        $now = now();

        return view('timesheet.create', [
            'defaultTask' => QuadrangSetting::get('default_task_description', ''),
            'defaultStartDate' => $now->copy()->startOfMonth()->toDateString(),
            'defaultEndDate' => $now->copy()->endOfMonth()->toDateString(),
        ]);
    }

    public function store(Request $request)
    {
        set_time_limit(300);

        try {
            $validated = $this->validateRange($request, required: true);
        } catch (ValidationException $e) {
            return $this->redirectBackWithError($request, $e->errors());
        }

        $this->service->setMonthYearFromDate($validated['start_date']);

        $created = $this->service->createTimeSheet();
        if (! $created) {
            return $this->redirectBackWithError($request, [
                '_' => 'Gagal membuat timesheet di Quadrang. Cek cookie / CSRF token di /settings.',
            ]);
        }

        $response = $this->service->getCurrentTimeSheet();
        $timesheetId = $this->service->findCurrentTimesheetId($response->body());

        if (! $timesheetId) {
            return back()->with('result', [
                'success' => false,
                'message' => 'Timesheet ID bulan ini tidak ditemukan di halaman Quadrang.',
            ])->withInput();
        }

        $result = $this->service->createTask(
            $timesheetId,
            $validated['task'],
            $validated['start_date'],
            $validated['end_date'],
            $request->boolean('skip_holidays', true),
        );

        return back()->with('result', [
            'success' => true,
            'message' => 'Task timesheet berhasil diproses.',
            'data' => $result,
        ])->withInput();
    }

    public function create(Request $request): JsonResponse
    {
        set_time_limit(300);

        try {
            $validated = $this->validateRange($request, required: false);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        }

        $task = $validated['task'] ?? QuadrangSetting::get('default_task_description', '');

        if (! empty($validated['start_date'])) {
            $this->service->setMonthYearFromDate($validated['start_date']);
        }

        $created = $this->service->createTimeSheet();
        if (! $created) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat timesheet di Quadrang.',
            ], 502);
        }

        $response = $this->service->getCurrentTimeSheet();
        $timesheetId = $this->service->findCurrentTimesheetId($response->body());

        if (! $timesheetId) {
            return response()->json([
                'success' => false,
                'message' => 'Timesheet ID tidak ditemukan.',
            ], 404);
        }

        $result = $this->service->createTask(
            $timesheetId,
            $task,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
            $request->boolean('skip_holidays', true),
        );

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'data' => $result,
        ], 201);
    }

    public function holidays(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $holidays = $this->service->getIndonesianHolidays(
            (int) $validated['year'],
            isset($validated['month']) ? (int) $validated['month'] : null,
        );

        return response()->json([
            'success' => true,
            'data' => collect($holidays)->map(fn (string $name, string $date) => [
                'date' => $date,
                'name' => $name,
            ])->values(),
        ]);
    }

    private function validateRange(Request $request, bool $required): array
    {
        $taskRules = $required ? ['required', 'string', 'max:1000'] : ['nullable', 'string', 'max:1000'];
        $dateRules = $required ? ['required', 'date'] : ['nullable', 'date'];

        $validated = $request->validate([
            'task' => $taskRules,
            'start_date' => $dateRules,
            'end_date' => array_merge($dateRules, ['after_or_equal:start_date']),
            'skip_holidays' => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['start_date']) && ! empty($validated['end_date'])) {
            $start = Carbon::parse($validated['start_date']);
            $end = Carbon::parse($validated['end_date']);

            if (! $start->isSameMonth($end)) {
                throw ValidationException::withMessages([
                    'end_date' => 'Date range harus berada pada bulan yang sama karena Quadrang timesheet diproses per bulan.',
                ]);
            }
        }

        return $validated;
    }

    private function redirectBackWithError(Request $request, array $errors)
    {
        return back()->withErrors($errors)->withInput();
    }
}
