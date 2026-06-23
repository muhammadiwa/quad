<?php

namespace App\Http\Controllers;

use App\Models\QuadrangSetting;
use App\Services\QuadrangService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(protected QuadrangService $service) {}

    public function index()
    {
        if ($redirect = $this->guardMissingCredentials()) {
            return $redirect;
        }

        return view('attendance.index', [
            'defaultLat' => QuadrangSetting::get('default_lat', ''),
            'defaultLon' => QuadrangSetting::get('default_lon', ''),
        ]);
    }

    public function clockIn(Request $request): JsonResponse|RedirectResponse
    {
        if ($redirect = $this->guardMissingCredentials()) {
            return $request->expectsJson() ? $this->jsonMissingCredentials() : $redirect;
        }

        $coords = $this->resolveCoordinates($request);
        if (! $coords['success']) {
            return $this->coordinateError($request, $coords);
        }

        $result = $this->service->clockIn($coords['lat'], $coords['lon']);

        return $this->attendanceResult($request, 'Clock in', $result, $coords);
    }

    public function clockOut(Request $request): JsonResponse|RedirectResponse
    {
        if ($redirect = $this->guardMissingCredentials()) {
            return $request->expectsJson() ? $this->jsonMissingCredentials() : $redirect;
        }

        $coords = $this->resolveCoordinates($request);
        if (! $coords['success']) {
            return $this->coordinateError($request, $coords);
        }

        $result = $this->service->clockOut($coords['lat'], $coords['lon']);

        return $this->attendanceResult($request, 'Clock out', $result, $coords);
    }

    private function resolveCoordinates(Request $request): array
    {
        $lat = $request->input('lat', QuadrangSetting::get('default_lat', ''));
        $lon = $request->input('lon', QuadrangSetting::get('default_lon', ''));

        if ($lat === '' || $lon === '' || $lat === null || $lon === null) {
            return [
                'success' => false,
                'status' => 400,
                'message' => 'Lat/lon belum diisi. Isi di form atau set default_lat / default_lon di /settings.',
            ];
        }

        if (! is_numeric($lat) || ! is_numeric($lon)
            || (float) $lat < -90 || (float) $lat > 90
            || (float) $lon < -180 || (float) $lon > 180) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Lat harus -90..90, lon harus -180..180.',
            ];
        }

        return [
            'success' => true,
            'lat' => (float) $lat,
            'lon' => (float) $lon,
        ];
    }

    private function coordinateError(Request $request, array $error): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $error['message'],
            ], $error['status']);
        }

        return back()->with('result', [
            'success' => false,
            'message' => $error['message'],
        ])->withInput();
    }

    private function attendanceResult(Request $request, string $action, array $result, array $coords): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return $this->jsonResult($action, $result, $coords);
        }

        return back()->with('result', [
            'success' => $result['success'],
            'status' => $result['status'],
            'lat' => $coords['lat'],
            'lon' => $coords['lon'],
            'message' => $result['success']
                ? "$action berhasil dicatat."
                : "$action gagal. Cek cookie / CSRF token di /settings.",
            'body_preview' => substr($result['body'], 0, 300),
        ])->withInput();
    }

    private function jsonResult(string $action, array $result, array $coords): JsonResponse
    {
        return response()->json([
            'success' => $result['success'],
            'status' => $result['status'],
            'lat' => $coords['lat'],
            'lon' => $coords['lon'],
            'message' => $result['success']
                ? "$action berhasil dicatat."
                : "$action gagal. Cek log untuk detail.",
            'body_preview' => substr($result['body'], 0, 300),
        ], $result['success'] ? 200 : 502);
    }

    private function jsonMissingCredentials(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Cookie / CSRF token belum diset di /settings.',
        ], 503);
    }

    private function guardMissingCredentials(): ?RedirectResponse
    {
        if (QuadrangSetting::get('cookie') === ''
            || QuadrangSetting::get('csrf_token') === '') {
            return redirect()->route('settings.edit')->with('result', [
                'success' => false,
                'message' => 'Cookie / CSRF token belum diset. Paste dari DevTools lalu simpan.',
            ]);
        }

        return null;
    }
}
