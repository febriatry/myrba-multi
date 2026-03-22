<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HrAttendanceCalculator
{
    public static function calculate(int $userId, string $date, ?string $clockInAt, ?string $clockOutAt): array
    {
        $profile = DB::table('hr_employee_profiles')->where('user_id', $userId)->first();
        $dayOfWeek = (int) Carbon::parse($date)->isoWeekday();
        $weeklyOffDays = [];
        if ($profile && !empty($profile->weekly_off_days)) {
            $weeklyOffDays = json_decode((string) $profile->weekly_off_days, true) ?: [];
        }
        $isWeeklyOff = in_array($dayOfWeek, $weeklyOffDays, true);
        $isHoliday = DB::table('hr_holidays')->where('date', $date)->where('is_active', 'Yes')->exists();

        $scheme = null;
        if ($profile && !empty($profile->work_scheme_id)) {
            $scheme = DB::table('hr_work_schemes')->where('id', (int) $profile->work_scheme_id)->first();
        }

        $in = $clockInAt ? Carbon::parse($clockInAt) : null;
        $out = $clockOutAt ? Carbon::parse($clockOutAt) : null;

        $scheduledStart = null;
        $scheduledEnd = null;
        $overtimeStart = null;
        $breakMinutes = $scheme ? (int) ($scheme->break_minutes_default ?? 0) : 0;
        $lateMinutes = 0;
        $workMinutes = 0;
        $overtimeMinutes = 0;
        $undertimeMinutes = 0;
        $isOffBySchemeRule = false;

        if ($scheme) {
            $type = strtolower(trim((string) ($scheme->type ?? 'fixed')));
            $grace = (int) ($scheme->grace_minutes ?? 0);
            $minWork = (int) ($scheme->min_work_minutes_per_day ?? 0);
            $otThreshold = (int) ($scheme->overtime_threshold_minutes ?? 0);

            if ($type === 'shift') {
                $roster = DB::table('hr_shift_rosters')->where('user_id', $userId)->where('date', $date)->first();
                if ($roster) {
                    $shift = DB::table('hr_shift_definitions')->where('id', (int) $roster->shift_id)->first();
                    if ($shift) {
                        $breakMinutes = (int) ($shift->break_minutes ?? $breakMinutes);
                        $scheduledStart = Carbon::parse($date . ' ' . (string) $shift->start_time);
                        $scheduledEnd = Carbon::parse($date . ' ' . (string) $shift->end_time);
                        if ($scheduledEnd->lessThanOrEqualTo($scheduledStart)) {
                            $scheduledEnd = $scheduledEnd->addDay();
                        }
                        $overtimeStart = $scheduledEnd ? $scheduledEnd->copy() : null;
                    }
                }
            } else {
                $rule = DB::table('hr_work_scheme_rules')->where('scheme_id', (int) $scheme->id)->where('day_of_week', $dayOfWeek)->first();
                if ($rule) {
                    $breakMinutes = $rule->break_minutes !== null ? (int) $rule->break_minutes : $breakMinutes;
                    if (empty($rule->start_time) || empty($rule->end_time)) {
                        $isOffBySchemeRule = true;
                        $minWork = 0;
                    }
                    if (!empty($rule->start_time) && !empty($rule->end_time)) {
                        $scheduledStart = Carbon::parse($date . ' ' . (string) $rule->start_time);
                        $scheduledEnd = Carbon::parse($date . ' ' . (string) $rule->end_time);
                        if ($scheduledEnd->lessThanOrEqualTo($scheduledStart)) {
                            $scheduledEnd = $scheduledEnd->addDay();
                        }
                    }

                    if (!empty($rule->overtime_start_time)) {
                        $overtimeStart = Carbon::parse($date . ' ' . (string) $rule->overtime_start_time);
                        if ($scheduledStart && $overtimeStart->lessThan($scheduledStart)) {
                            $overtimeStart = $overtimeStart->addDay();
                        }
                    } elseif ($scheduledEnd) {
                        $overtimeStart = $scheduledEnd->copy();
                    }

                    if ($type === 'flexible') {
                        $windowEnd = !empty($rule->flex_window_end) ? Carbon::parse($date . ' ' . (string) $rule->flex_window_end) : null;
                        if ($windowEnd && $in && $in->greaterThan($windowEnd)) {
                            $lateMinutes = (int) $windowEnd->diffInMinutes($in);
                        }
                    }
                }
            }

            if ($in && $scheduledStart) {
                $lateBoundary = $scheduledStart->copy()->addMinutes($grace);
                if ($in->greaterThan($lateBoundary)) {
                    $lateMinutes = max($lateMinutes, (int) $lateBoundary->diffInMinutes($in));
                }
            }

            if ($in && $out && $out->greaterThan($in)) {
                $workMinutes = max(0, (int) $in->diffInMinutes($out) - $breakMinutes);
                if ($overtimeStart) {
                    $otBoundary = $overtimeStart->copy()->addMinutes($otThreshold);
                    if ($out->greaterThan($otBoundary)) {
                        $overtimeMinutes = (int) $otBoundary->diffInMinutes($out);
                    }
                } elseif ($minWork > 0 && $workMinutes > $minWork) {
                    $overtimeMinutes = $workMinutes - $minWork;
                }
                if ($minWork > 0 && $workMinutes < $minWork) {
                    $undertimeMinutes = $minWork - $workMinutes;
                }
            }
        } else {
            if ($in && $out && $out->greaterThan($in)) {
                $workMinutes = (int) $in->diffInMinutes($out);
            }
        }

        if (($isHoliday || $isWeeklyOff) && $workMinutes > 0) {
            $overtimeMinutes = $workMinutes;
            $lateMinutes = 0;
            $undertimeMinutes = 0;
        }
        if ($isOffBySchemeRule && $workMinutes > 0) {
            $overtimeMinutes = $workMinutes;
            $lateMinutes = 0;
            $undertimeMinutes = 0;
        }

        return [
            'scheduled_start_at' => $scheduledStart ? $scheduledStart->toDateTimeString() : null,
            'scheduled_end_at' => $scheduledEnd ? $scheduledEnd->toDateTimeString() : null,
            'break_minutes' => $breakMinutes,
            'late_minutes' => $lateMinutes,
            'work_minutes' => $workMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'undertime_minutes' => $undertimeMinutes,
        ];
    }
}
