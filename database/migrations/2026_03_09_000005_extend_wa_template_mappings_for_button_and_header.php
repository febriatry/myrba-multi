<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_template_mappings', function (Blueprint $table) {
            $table->unsignedInteger('component_index')->default(0)->after('component_type');
            $table->string('component_sub_type')->nullable()->after('component_index');
            $table->dropUnique('wa_template_mapping_unique');
            $table->unique(
                ['template_id', 'message_type', 'component_type', 'component_index', 'param_index'],
                'wa_template_mapping_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('wa_template_mappings', function (Blueprint $table) {
            $table->dropUnique('wa_template_mapping_unique');
            $table->unique(
                ['template_id', 'message_type', 'component_type', 'param_index'],
                'wa_template_mapping_unique'
            );
            $table->dropColumn(['component_sub_type', 'component_index']);
        });
    }
};
