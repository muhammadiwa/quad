<?php

namespace App\Console\Commands;

use App\Models\QuadrangSetting;
use App\Models\TaskTemplate;
use App\Services\QuadrangService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AutoAttendanceCommand extends Command
{
    protected $signature = 'attendance:auto
        {--action= : clock-in, clock-out, or both}
        {--force : Ignore enabled flag, workday, time window, and daily marker}
        {--dry-run : Print the decision without sending attendance to Quadrang}';

    protected $description = 'Automatically clock in/out on workdays according to the default work schedule.';

    public function handle(QuadrangService $service): int
    {
        $timezone = QuadrangSetting::get('auto_attendance_timezone', 'Asia/Jakarta') ?: 'Asia/Jakarta';
        $now = Carbon::now($timezone);
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');
        $actionOption = $this->option('action') ?: 'both';

        if (! in_array($actionOption, ['clock-in', 'clock-out', 'both'], true)) {
            $this->error('--action must be clock-in, clock-out, or both.');

            return self::FAILURE;
        }

        if (! $force && ! $this->enabled()) {
            return $this->skip('auto attendance is disabled', [
                'now' => $now->toDateTimeString(),
                'timezone' => $timezone,
            ]);
        }

        $template = TaskTemplate::default();
        $clockInTime = $this->settingTime('auto_clock_in_time')
            ?? $template?->start_at?->format('H:i')
            ?? '07:30';
        $clockOutTime = $this->settingTime('auto_clock_out_time')
            ?? $template?->end_at?->format('H:i')
            ?? '16:30';
        $windowMinutes = max(1, (int) (QuadrangSetting::get('auto_attendance_window_minutes', '5') ?: 5));

        $actions = $actionOption === 'both' ? ['clock-in', 'clock-out'] : [$actionOption];
        $sent = 0;

        foreach ($actions as $action) {
            $targetTime = $action === 'clock-in' ? $clockInTime : $clockOutTime;
            $decision = $this->decision($service, $now, $action, $targetTime, $windowMinutes, $force);

            if (! $decision['due']) {
                $this->line("Skip {$action}: {$decision['reason']}");
                Log::info('attendance.auto.skip', $decision);

                continue;
            }

            if ($dryRun) {
                $this->info("Dry run {$action}: would send at {$now->toDateTimeString()} {$timezone}");
                Log::info('attendance.auto.dry_run', $decision);

                continue;
            }

            $markerKey = $this->markerKey($now, $action);
            if (! $force && ! Cache::add($markerKey, 'running', now()->addMinutes($windowMinutes))) {
                $this->line("Skip {$action}: already processed today");
                Log::info('attendance.auto.skip', array_merge($decision, ['reason' => 'already processed today']));

                continue;
            }

            $result = $this->send($service, $action, $decision['lat'], $decision['lon']);
            $markerValue = [
                'success' => $result['success'],
                'status' => $result['status'],
                'sent_at' => $now->toDateTimeString(),
                'lat' => $decision['lat'],
                'lon' => $decision['lon'],
            ];

            Cache::put(
                $markerKey,
                $markerValue,
                $result['success'] ? now()->addHours(36) : now()->addMinutes($windowMinutes)
            );

            Log::info('attendance.auto.sent', array_merge($markerValue, [
                'action' => $action,
                'body_first_300' => substr($result['body'], 0, 300),
            ]));

            if (! $result['success']) {
                $this->error("Failed {$action}: HTTP {$result['status']}");

                return self::FAILURE;
            }

            $this->info("Sent {$action}: HTTP {$result['status']}");
            $sent++;
        }

        return self::SUCCESS;
    }

    private function enabled(): bool
    {
        return in_array(strtolower((string) QuadrangSetting::get('auto_attendance_enabled', '0')), ['1', 'true', 'yes', 'on'], true);
    }

    private function settingTime(string $key): ?string
    {
        $value = trim((string) QuadrangSetting::get($key, ''));

        return preg_match('/^\d{2}:\d{2}$/', $value) ? $value : null;
    }

    private function decision(
        QuadrangService $service,
        Carbon $now,
        string $action,
        string $targetTime,
        int $windowMinutes,
        bool $force,
    ): array {
        $base = [
            'action' => $action,
            'now' => $now->toDateTimeString(),
            'target_time' => $targetTime,
            'window_minutes' => $windowMinutes,
            'date' => $now->toDateString(),
        ];

        $lat = QuadrangSetting::get('default_lat', '');
        $lon = QuadrangSetting::get('default_lon', '');
        $cookie = QuadrangSetting::get('cookie', '');
        $csrfToken = QuadrangSetting::get('csrf_token', '');

        if ($cookie === '' || $csrfToken === '' || $cookie === null || $csrfToken === null) {
            return array_merge($base, [
                'due' => false,
                'reason' => 'cookie/csrf_token is missing',
            ]);
        }

        if ($lat === '' || $lon === '' || $lat === null || $lon === null) {
            return array_merge($base, [
                'due' => false,
                'reason' => 'default_lat/default_lon is missing',
            ]);
        }

        if (! is_numeric($lat) || ! is_numeric($lon)) {
            return array_merge($base, [
                'due' => false,
                'reason' => 'default_lat/default_lon is invalid',
            ]);
        }

        $base['lat'] = (float) $lat;
        $base['lon'] = (float) $lon;

        if (! $force && $now->isWeekend()) {
            return array_merge($base, [
                'due' => false,
                'reason' => 'weekend',
            ]);
        }

        if (! $force && ! $this->withinWindow($now, $targetTime, $windowMinutes)) {
            return array_merge($base, [
                'due' => false,
                'reason' => 'outside time window',
            ]);
        }

        if (! $force && Cache::has($this->markerKey($now, $action))) {
            return array_merge($base, [
                'due' => false,
                'reason' => 'already processed today',
            ]);
        }

        if (! $force) {
            $holidays = $service->getIndonesianHolidays((int) $now->year, (int) $now->month);
            if (array_key_exists($now->toDateString(), $holidays)) {
                return array_merge($base, [
                    'due' => false,
                    'reason' => 'public holiday: '.$holidays[$now->toDateString()],
                ]);
            }
        }

        return array_merge($base, [
            'due' => true,
            'reason' => 'due',
        ]);
    }

    private function withinWindow(Carbon $now, string $targetTime, int $windowMinutes): bool
    {
        [$hour, $minute] = array_map('intval', explode(':', $targetTime));
        $start = $now->copy()->setTime($hour, $minute);
        $end = $start->copy()->addMinutes($windowMinutes);

        return $now->betweenIncluded($start, $end);
    }

    private function markerKey(Carbon $now, string $action): string
    {
        return "attendance.auto.{$now->toDateString()}.{$action}";
    }

    private function send(QuadrangService $service, string $action, float $lat, float $lon): array
    {
        return $action === 'clock-in'
            ? $service->clockIn($lat, $lon)
            : $service->clockOut($lat, $lon);
    }

    private function skip(string $reason, array $context): int
    {
        $this->line('Skip: '.$reason);
        Log::info('attendance.auto.skip', array_merge($context, ['reason' => $reason]));

        return self::SUCCESS;
    }
}
