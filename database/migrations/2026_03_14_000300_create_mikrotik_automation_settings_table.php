<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mikrotik_automation_settings')) {
            Schema::create('mikrotik_automation_settings', function (Blueprint $table) {
                $table->id();
                $table->enum('is_enabled', ['Yes', 'No'])->default('No');
                $table->enum('respect_pelanggan_auto_isolir', ['Yes', 'No'])->default('Yes');
                $table->unsignedInteger('min_unpaid_invoices')->default(1);
                $table->enum('overdue_only', ['Yes', 'No'])->default('Yes');
                $table->enum('include_waiting_review', ['Yes', 'No'])->default('Yes');
                $table->enum('scope_type', ['All', 'AreaCoverage'])->default('All');
                $table->json('scope_area_ids')->nullable();
                $table->unsignedInteger('max_execute_per_run')->default(200);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mikrotik_automation_settings');
    }
};

