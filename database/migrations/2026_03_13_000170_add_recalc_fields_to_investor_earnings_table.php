<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('investor_earnings')) {
            Schema::table('investor_earnings', function (Blueprint $table) {
                if (!Schema::hasColumn('investor_earnings', 'is_reversed')) {
                    $table->string('is_reversed', 5)->default('No')->after('amount');
                }
                if (!Schema::hasColumn('investor_earnings', 'reversed_at')) {
                    $table->timestamp('reversed_at')->nullable()->after('is_reversed');
                }
                if (!Schema::hasColumn('investor_earnings', 'reversed_by')) {
                    $table->unsignedBigInteger('reversed_by')->nullable()->after('reversed_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('investor_earnings')) {
            Schema::table('investor_earnings', function (Blueprint $table) {
                if (Schema::hasColumn('investor_earnings', 'reversed_by')) {
                    $table->dropColumn('reversed_by');
                }
                if (Schema::hasColumn('investor_earnings', 'reversed_at')) {
                    $table->dropColumn('reversed_at');
                }
                if (Schema::hasColumn('investor_earnings', 'is_reversed')) {
                    $table->dropColumn('is_reversed');
                }
            });
        }
    }
};

