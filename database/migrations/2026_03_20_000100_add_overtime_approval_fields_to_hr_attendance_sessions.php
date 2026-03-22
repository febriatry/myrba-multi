<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hr_attendance_sessions')) {
            return;
        }

        Schema::table('hr_attendance_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_attendance_sessions', 'overtime_review_status')) {
                $table->string('overtime_review_status', 20)->nullable()->after('overtime_minutes');
            }
            if (!Schema::hasColumn('hr_attendance_sessions', 'overtime_approved_minutes')) {
                $table->unsignedInteger('overtime_approved_minutes')->default(0)->after('overtime_review_status');
            }
            if (!Schema::hasColumn('hr_attendance_sessions', 'overtime_reviewed_by')) {
                $table->unsignedBigInteger('overtime_reviewed_by')->nullable()->after('overtime_approved_minutes');
            }
            if (!Schema::hasColumn('hr_attendance_sessions', 'overtime_reviewed_at')) {
                $table->dateTime('overtime_reviewed_at')->nullable()->after('overtime_reviewed_by');
            }
        });

        DB::table('hr_attendance_sessions')
            ->where('overtime_minutes', '>', 0)
            ->whereNull('overtime_review_status')
            ->update([
                'overtime_review_status' => 'pending',
                'overtime_approved_minutes' => 0,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('hr_attendance_sessions')) {
            return;
        }

        Schema::table('hr_attendance_sessions', function (Blueprint $table) {
            foreach ([
                'overtime_review_status',
                'overtime_approved_minutes',
                'overtime_reviewed_by',
                'overtime_reviewed_at',
            ] as $col) {
                if (Schema::hasColumn('hr_attendance_sessions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
