<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pelanggans')) {
            Schema::table('pelanggans', function (Blueprint $table) {
                if (!Schema::hasColumn('pelanggans', 'material_status')) {
                    $table->string('material_status', 20)->default('Pending')->after('status_berlangganan');
                }
                if (!Schema::hasColumn('pelanggans', 'material_approved_by')) {
                    $table->unsignedBigInteger('material_approved_by')->nullable()->after('material_status');
                }
                if (!Schema::hasColumn('pelanggans', 'material_approved_at')) {
                    $table->timestamp('material_approved_at')->nullable()->after('material_approved_by');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pelanggans')) {
            Schema::table('pelanggans', function (Blueprint $table) {
                foreach (['material_approved_at', 'material_approved_by', 'material_status'] as $col) {
                    if (Schema::hasColumn('pelanggans', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

