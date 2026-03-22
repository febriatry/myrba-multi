<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('olts')) {
            return;
        }
        Schema::create('olts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
			$table->enum('type', ['Zte', 'Huawei']);
			$table->string('host', 100);
			$table->integer('telnet_port');
			$table->string('telnet_username', 100);
			$table->string('telnet_password', 100);
			$table->integer('snmp_port');
			$table->string('ro_community', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('olts');
    }
};
