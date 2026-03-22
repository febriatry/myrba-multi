<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\HrOperationalCalculator;

class HrPayrollGenerator
{
    public static function generate(int $periodId): array
    {
        $period = DB::table('hr_payroll_periods')->where('id', $periodId)->first();
        if (!$period) {
            return ['ok' => false, 'message' => 'Periode tidak ditemukan.'];
        }
        if ((string) $period->status === 'locked') {
            return ['ok' => false, 'message' => 'Periode payroll sudah dikunci.'];
        }

        $start = Carbon::parse((string) $period->period_start)->startOfDay();
        $end = Carbon::parse((string) $period->period_end)->endOfDay();

        $employees = DB::table('hr_employee_profiles as ep')
            ->join('users as u', 'ep.user_id', '=', 'u.id')
            ->where('ep.is_active', 'Yes')
            ->select(
                'ep.user_id',
                'u.name as user_name',
                'ep.salary_type',
                'ep.monthly_salary',
                'ep.daily_salary',
                'ep.overtime_rate_per_hour',
                'ep.operational_daily_rate',
                'ep.mandatory_deduction_type',
                'ep.mandatory_deduction_value'
            )
            ->orderBy('u.name')
            ->get();

        $attendanceAgg = DB::table('hr_attendance_sessions')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('clock_in_at')
            ->where('review_status', 'approved')
            ->groupBy('user_id')
            ->select(
                'user_id',
                DB::raw('COUNT(DISTINCT date) as present_days'),
                DB::raw('SUM(work_minutes) as work_minutes'),
                DB::raw("SUM(CASE WHEN overtime_review_status = 'approved' THEN overtime_approved_minutes ELSE 0 END) as overtime_minutes")
            )
            ->get()
            ->keyBy('user_id');

        $attendanceDates = DB::table('hr_attendance_sessions')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('clock_in_at')
            ->where('review_status', 'approved')
            ->select('user_id', 'date')
            ->distinct()
            ->get();
        $attendanceDatesByUser = [];
        foreach ($attendanceDates as $ad) {
            $uid = (int) $ad->user_id;
            if (!isset($attendanceDatesByUser[$uid])) {
                $attendanceDatesByUser[$uid] = [];
            }
            $attendanceDatesByUser[$uid][] = (string) $ad->date;
        }

        $operationalManualAgg = collect();
        if (Schema::hasColumn('hr_operational_dailies', 'source')) {
            $operationalManualAgg = DB::table('hr_operational_dailies')
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->where('source', 'manual')
                ->groupBy('user_id')
                ->select('user_id', DB::raw('SUM(amount) as amount'))
                ->get()
                ->keyBy('user_id');
        }

        $sanctionAgg = DB::table('hr_sanctions')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('user_id')
            ->select('user_id', DB::raw('SUM(amount) as amount'))
            ->get()
            ->keyBy('user_id');

        $otherDeductionAgg = DB::table('hr_deductions')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('user_id')
            ->select('user_id', DB::raw('SUM(amount) as amount'))
            ->get()
            ->keyBy('user_id');

        $kasbonDeductionAgg = DB::table('hr_kasbon_repayments')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('source', 'payroll')
            ->groupBy('user_id')
            ->select('user_id', DB::raw('SUM(amount) as amount'))
            ->get()
            ->keyBy('user_id');

        DB::table('hr_payroll_items')->where('period_id', $periodId)->delete();

        $rows = [];
        $summary = [
            'employees' => 0,
            'present_days' => 0,
            'base_total' => 0,
            'overtime_total' => 0,
            'operational_total' => 0,
            'mandatory_total' => 0,
            'sanction_total' => 0,
            'other_deduction_total' => 0,
            'kasbon_deduction_total' => 0,
            'grand_total' => 0,
        ];

        foreach ($employees as $e) {
            $uid = (int) $e->user_id;
            $att = $attendanceAgg->get($uid);
            $presentDays = (int) ($att->present_days ?? 0);
            $workMinutes = (int) ($att->work_minutes ?? 0);
            $overtimeMinutes = (int) ($att->overtime_minutes ?? 0);

            $salaryType = strtolower(trim((string) ($e->salary_type ?? 'monthly')));
            $monthlySalary = (int) ($e->monthly_salary ?? 0);
            $dailySalary = (int) ($e->daily_salary ?? 0);
            $overtimeRate = (int) ($e->overtime_rate_per_hour ?? 0);

            $baseAmount = $salaryType === 'daily' ? ($dailySalary * $presentDays) : $monthlySalary;
            $overtimeAmount = (int) round(($overtimeMinutes / 60.0) * $overtimeRate);
            $operationalManual = (int) (data_get($operationalManualAgg, $uid . '.amount') ?? 0);
            $operationalAuto = 0;
            foreach (($attendanceDatesByUser[$uid] ?? []) as $d) {
                $operationalAuto += HrOperationalCalculator::amountFor($uid, $d);
            }
            $operationalAmount = $operationalAuto + $operationalManual;

            $mandatoryType = strtolower(trim((string) ($e->mandatory_deduction_type ?? 'fixed')));
            $mandatoryValue = (int) ($e->mandatory_deduction_value ?? 0);
            $mandatoryAmount = 0;
            if ($mandatoryType === 'percent') {
                $mandatoryAmount = (int) round(($mandatoryValue / 100.0) * $baseAmount);
            } else {
                $mandatoryAmount = $mandatoryValue;
            }

            $sanctionAmount = (int) (data_get($sanctionAgg, $uid . '.amount') ?? 0);
            $otherDeductionAmount = (int) (data_get($otherDeductionAgg, $uid . '.amount') ?? 0);
            $kasbonDeductionAmount = (int) (data_get($kasbonDeductionAgg, $uid . '.amount') ?? 0);

            $total = max(0, (int) ($baseAmount + $overtimeAmount + $operationalAmount - $mandatoryAmount - $sanctionAmount - $otherDeductionAmount - $kasbonDeductionAmount));

            $meta = [
                'salary_type' => $salaryType,
                'operational_auto' => $operationalAuto,
                'operational_manual' => $operationalManual,
                'mandatory_type' => $mandatoryType,
                'mandatory_value' => $mandatoryValue,
            ];

            $rows[] = [
                'period_id' => $periodId,
                'user_id' => $uid,
                'present_days' => $presentDays,
                'work_minutes' => $workMinutes,
                'overtime_minutes' => $overtimeMinutes,
                'base_amount' => $baseAmount,
                'overtime_amount' => $overtimeAmount,
                'operational_amount' => $operationalAmount,
                'mandatory_deduction_amount' => $mandatoryAmount,
                'sanction_deduction_amount' => $sanctionAmount,
                'other_deduction_amount' => $otherDeductionAmount,
                'kasbon_deduction_amount' => $kasbonDeductionAmount,
                'total_amount' => $total,
                'meta' => json_encode($meta),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $summary['employees']++;
            $summary['present_days'] += $presentDays;
            $summary['base_total'] += $baseAmount;
            $summary['overtime_total'] += $overtimeAmount;
            $summary['operational_total'] += $operationalAmount;
            $summary['mandatory_total'] += $mandatoryAmount;
            $summary['sanction_total'] += $sanctionAmount;
            $summary['other_deduction_total'] += $otherDeductionAmount;
            $summary['kasbon_deduction_total'] += $kasbonDeductionAmount;
            $summary['grand_total'] += $total;
        }

        if (!empty($rows)) {
            DB::table('hr_payroll_items')->insert($rows);
        }

        DB::table('hr_payroll_periods')->where('id', $periodId)->update([
            'generated_by' => auth()->id(),
            'generated_at' => now(),
            'updated_at' => now(),
        ]);

        return ['ok' => true, 'summary' => $summary];
    }
}
