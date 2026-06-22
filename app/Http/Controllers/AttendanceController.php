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

        return view('attendance.index', [
            'defaultLat' => QuadrangSetting::get('default_lat', ''),
            'defaultLon' => QuadrangSetting::get('default_lon', ''),
        ]);
    }

    public function clockIn(Request $request): JsonResponse
    {
        if ($redirect = $this->guardMissingCredentials()) {
            return $this->jsonMissingCredentials();
        }

        $coords = $this->resolveCoordinates($request);
        if ($coords instanceof JsonResponse) {
            return $coords;
        }

        $result = $this->service->clockIn($coords['lat'], $coords['lon']);

        return $this->jsonResult('Clock in', $result, $coords);
    }

    public function clockOut(Request $request): JsonResponse
    {
        if ($redirect = $this->guardMissingCredentials()) {
            return $this->jsonMissingCredentials();
        }

        $coords = $this->resolveCoordinates($request);
        if ($coords instanceof JsonResponse) {
            return $coords;
        }

        $result = $this->service->clockOut($coords['lat'], $coords['lon']);

        return $this->jsonResult('Clock out', $result, $coords);
    }

    private function resolveCoordinates(Request $request): array|JsonResponse
    {
        $lat = $request->input('lat', QuadrangSetting::get('default_lat', ''));
        $lon = $request->input('lon', QuadrangSetting::get('default_lon', ''));

        if ($lat === '' || $lon === '' || $lat === null || $lon === null) {
            return response()->json([
                'success' => false,
                'message' => 'Lat/lon belum diisi. Isi di form atau set default_lat / default_lon di /settings.',
            ], 400);
        }

        if (! is_numeric($lat) || ! is_numeric($lon)
            || (float) $lat < -90 || (float) $lat > 90
            || (float) $lon < -180 || (float) $lon > 180) {
            return response()->json([
                'success' => false,
                'message' => 'Lat harus -90..90, lon harus -180..180.',
            ], 422);
        }

        return ['lat' => (float) $lat, 'lon' => (float) $lon];
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
