<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HrOperationalCalculator
{
    public static function amountFor(int $userId, string $date): int
    {
        $d = Carbon::parse($date);
        $dow = (int) $d->isoWeekday();

        $rule = DB::table('hr_operational_rules')
            ->where('is_active', 'Yes')
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id')->orWhere('user_id', $userId);
            })
            ->where(function ($q) use ($date, $dow) {
                $q->where('date', $date)
                    ->orWhere(function ($q2) use ($dow) {
                        $q2->whereNull('date')->where('day_of_week', $dow);
                    });
            })
            ->orderByRaw('CASE WHEN user_id IS NULL THEN 0 ELSE 1 END DESC')
            ->orderByRaw('CASE WHEN date IS NULL THEN 0 ELSE 1 END DESC')
            ->orderByDesc('id')
            ->first();

        if ($rule) {
            return (int) $rule->amount;
        }

        $profile = DB::table('hr_employee_profiles')->where('user_id', $userId)->first();
        return (int) ($profile->operational_daily_rate ?? 0);
    }
}

