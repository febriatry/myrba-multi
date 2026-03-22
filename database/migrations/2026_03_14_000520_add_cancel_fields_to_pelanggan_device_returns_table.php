<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pelanggan_device_returns')) {
            Schema::table('pelanggan_device_returns', function (Blueprint $table) {
                if (!Schema::hasColumn('pelanggan_device_returns', 'is_cancelled')) {
                    $table->enum('is_cancelled', ['Yes', 'No'])->default('No')->after('created_by');
                }
                if (!Schema::hasColumn('pelanggan_device_returns', 'cancelled_by')) {
                    $table->unsignedBigInteger('cancelled_by')->nullable()->after('is_cancelled');
                }
                if (!Schema::hasColumn('pelanggan_device_returns', 'cancelled_at')) {
                    $table->dateTime('cancelled_at')->nullable()->after('cancelled_by');
                }
                if (!Schema::hasColumn('pelanggan_device_returns', 'cancel_reason')) {
                    $table->string('cancel_reason', 255)->nullable()->after('cancelled_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pelanggan_device_returns')) {
            Schema::table('pelanggan_device_returns', function (Blueprint $table) {
                foreach (['cancel_reason', 'cancelled_at', 'cancelled_by', 'is_cancelled'] as $col) {
                    if (Schema::hasColumn('pelanggan_device_returns', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

