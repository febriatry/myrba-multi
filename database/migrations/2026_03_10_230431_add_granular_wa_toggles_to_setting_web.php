<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('setting_web', function (Blueprint $table) {
            $table->enum('is_wa_billing_active', ['Yes', 'No'])->default('Yes')->after('is_wa_broadcast_active');
            $table->enum('is_wa_payment_active', ['Yes', 'No'])->default('Yes')->after('is_wa_billing_active');
            $table->enum('is_wa_welcome_active', ['Yes', 'No'])->default('Yes')->after('is_wa_payment_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('setting_web', function (Blueprint $table) {
            $table->dropColumn(['is_wa_billing_active', 'is_wa_payment_active', 'is_wa_welcome_active']);
        });
    }
};
