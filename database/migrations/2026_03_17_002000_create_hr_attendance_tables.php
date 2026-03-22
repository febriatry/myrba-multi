<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hr_jabatans')) {
            Schema::create('hr_jabatans', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->unsignedInteger('rank_order')->default(0)->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hr_work_schemes')) {
            Schema::create('hr_work_schemes', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->string('type', 20);
                $table->unsignedInteger('grace_minutes')->default(0);
                $table->unsignedInteger('break_minutes_default')->default(0);
                $table->unsignedInteger('min_work_minutes_per_day')->default(0);
                $table->unsignedInteger('overtime_threshold_minutes')->default(0);
                $table->json('late_policy')->nullable();
                $table->json('undertime_policy')->nullable();
                $table->json('overtime_policy')->nullable();
                $table->string('is_active', 5)->default('Yes')->index();
                $table->timestamps();
                $table->index(['type', 'is_active']);
            });
        }

        if (!Schema::hasTable('hr_work_scheme_rules')) {
            Schema::create('hr_work_scheme_rules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('scheme_id')->index();
                $table->unsignedTinyInteger('day_of_week')->index();
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->time('flex_window_start')->nullable();
                $table->time('flex_window_end')->nullable();
                $table->time('core_start')->nullable();
                $table->time('core_end')->nullable();
                $table->unsignedInteger('break_minutes')->nullable();
                $table->timestamps();
                $table->unique(['scheme_id', 'day_of_week'], 'hr_work_scheme_day_unique');
            });
        }

        if (!Schema::hasTable('hr_shift_definitions')) {
            Schema::create('hr_shift_definitions', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->time('start_time');
                $table->time('end_time');
                $table->unsignedInteger('break_minutes')->default(0);
                $table->string('is_active', 5)->default('Yes')->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hr_employee_profiles')) {
            Schema::create('hr_employee_profiles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->unsignedBigInteger('jabatan_id')->nullable()->index();
                $table->unsignedBigInteger('work_scheme_id')->nullable()->index();
                $table->string('employee_code', 50)->nullable()->unique();
                $table->string('is_active', 5)->default('Yes')->index();
                $table->date('joined_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hr_shift_rosters')) {
            Schema::create('hr_shift_rosters', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->date('date')->index();
                $table->unsignedBigInteger('shift_id')->index();
                $table->string('status', 20)->default('planned')->index();
                $table->string('notes', 255)->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'date'], 'hr_shift_roster_user_date_unique');
            });
        }

        if (!Schema::hasTable('hr_attendance_sessions')) {
            Schema::create('hr_attendance_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->date('date')->index();
                $table->string('work_type', 20)->default('normal')->index();
                $table->string('status', 20)->default('open')->index();
                $table->dateTime('clock_in_at')->nullable();
                $table->dateTime('clock_out_at')->nullable();
                $table->decimal('clock_in_lat', 10, 7)->nullable();
                $table->decimal('clock_in_lng', 10, 7)->nullable();
                $table->decimal('clock_out_lat', 10, 7)->nullable();
                $table->decimal('clock_out_lng', 10, 7)->nullable();
                $table->dateTime('scheduled_start_at')->nullable();
                $table->dateTime('scheduled_end_at')->nullable();
                $table->unsignedInteger('break_minutes')->default(0);
                $table->unsignedInteger('late_minutes')->default(0);
                $table->unsignedInteger('work_minutes')->default(0);
                $table->unsignedInteger('overtime_minutes')->default(0);
                $table->unsignedInteger('undertime_minutes')->default(0);
                $table->json('flags')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->dateTime('reviewed_at')->nullable();
                $table->string('review_status', 20)->nullable()->index();
                $table->string('notes', 255)->nullable();
                $table->timestamps();
                $table->index(['user_id', 'date', 'work_type']);
            });
        }

        if (!Schema::hasTable('hr_attendance_tracks')) {
            Schema::create('hr_attendance_tracks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('session_id')->index();
                $table->dateTime('tracked_at')->index();
                $table->decimal('lat', 10, 7);
                $table->decimal('lng', 10, 7);
                $table->float('accuracy')->nullable();
                $table->float('speed')->nullable();
                $table->float('bearing')->nullable();
                $table->string('provider', 50)->nullable();
                $table->string('is_mock', 5)->default('No')->index();
                $table->string('device_id', 120)->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index(['session_id', 'tracked_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_attendance_tracks');
        Schema::dropIfExists('hr_attendance_sessions');
        Schema::dropIfExists('hr_shift_rosters');
        Schema::dropIfExists('hr_employee_profiles');
        Schema::dropIfExists('hr_shift_definitions');
        Schema::dropIfExists('hr_work_scheme_rules');
        Schema::dropIfExists('hr_work_schemes');
        Schema::dropIfExists('hr_jabatans');
    }
};

