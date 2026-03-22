<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hr_attendance_sessions')) {
            return;
        }

        Schema::table('hr_attendance_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_attendance_sessions', 'overtime_review_note')) {
                $table->string('overtime_review_note', 255)->nullable()->after('overtime_reviewed_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('hr_attendance_sessions')) {
            return;
        }

        Schema::table('hr_attendance_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('hr_attendance_sessions', 'overtime_review_note')) {
                $table->dropColumn('overtime_review_note');
            }
        });
    }
};

