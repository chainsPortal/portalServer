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
        Schema::create('networks', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("symbol");
            $table->string("logo")->nullable();
            $table->string("chain_id");
            $table->string("rpc")->nullable();
            $table->string("explorer")->nullable();
            $table->string("bridge_address");
            $table->string("settings_address");
            $table->string("deployer_address");
            $table->string("controller_address");
            $table->string("feeController_address");
            $table->string("registry_address");
            $table->string("status");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('networks');
    }
};
