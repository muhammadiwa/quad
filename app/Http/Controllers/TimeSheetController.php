<?php

namespace App\Http\Controllers;

use App\Services\QuadrangService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimeSheetController extends Controller
{
    public function __construct(protected QuadrangService $service) {}

    /**
     * @throws ConnectionException
     */
    public function create(Request $request): JsonResponse
    {
        $task = $request->has('task') ? $request->input('task') : 'Developing & Fixing Cross Border Service';

        $created = $this->service->createTimeSheet();
        $response = $this->service->getCurrentTimeSheet();
        preg_match_all(
            '#href="/attendance/task/(\d+)"#',
            $response->body(),
            $matches
        );

        $ids = array_map('strval', $matches[1]);

        if (! empty($ids)) {
            $result = $this->service->createTask($ids[0], $task);

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
