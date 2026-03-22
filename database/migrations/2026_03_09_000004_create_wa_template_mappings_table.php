<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_template_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('template_id');
            $table->string('message_type');
            $table->string('component_type');
            $table->unsignedInteger('param_index');
            $table->string('source_key');
            $table->string('parameter_type')->default('text');
            $table->text('default_value')->nullable();
            $table->enum('is_required', ['Yes', 'No'])->default('Yes');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['template_id', 'message_type']);
            $table->unique(['template_id', 'message_type', 'component_type', 'param_index'], 'wa_template_mapping_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_template_mappings');
    }
};
