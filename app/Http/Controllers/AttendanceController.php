<?php

namespace App\Http\Controllers;

use App\Models\QuadrangSetting;
use App\Services\QuadrangService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(protected QuadrangService $service) {}

    public function index()
    {
        if ($redirect = $this->guardMissingCredentials()) {
            return $redirect;
        }

        return view('attendance.index');
    }

    public function clockIn(Request $request): JsonResponse
    {
        if ($redirect = $this->guardMissingCredentials()) {
            return response()->json([
                'success' => false,
                'message' => 'Cookie / CSRF token belum diset di /settings.',
            ], 503);
        }

        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $result = $this->service->clockIn((float) $data['lat'], (float) $data['lon']);

        return response()->json([
            'success' => $result['success'],
            'status' => $result['status'],
            'message' => $result['success']
                ? 'Clock in berhasil dicatat.'
                : 'Clock in gagal. Cek log untuk detail.',
            'body_preview' => substr($result['body'], 0, 300),
        ], $result['success'] ? 200 : 502);
    }

    public function clockOut(Request $request): JsonResponse
    {
        if ($redirect = $this->guardMissingCredentials()) {
            return response()->json([
                'success' => false,
                'message' => 'Cookie / CSRF token belum diset di /settings.',
            ], 503);
        }

        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $result = $this->service->clockOut((float) $data['lat'], (float) $data['lon']);

        return response()->json([
            'success' => $result['success'],
            'status' => $result['status'],
            'message' => $result['success']
                ? 'Clock out berhasil dicatat.'
                : 'Clock out gagal. Cek log untuk detail.',
            'body_preview' => substr($result['body'], 0, 300),
        ], $result['success'] ? 200 : 502);
    }

    private function guardMissingCredentials()
    {
        if (QuadrangSetting::get('cookie') === ''
            || QuadrangSetting::get('csrf_token') === '') {
            if (request()->expectsJson()) {
                return false;
            }

            return redirect()->route('settings.edit')->with('result', [
                'success' => false,
                'message' => 'Cookie / CSRF token belum diset. Paste dari DevTools lalu simpan.',
            ]);
        }

        return null;
    }
}
