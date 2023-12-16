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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string("chain_id");
            $table->string("asset_address");
            $table->string("type");
            $table->string("name");
            $table->string("symbol");
            $table->string("logo")->nullable();
            $table->string("chain_from_id");
            $table->string("chain_from_asset_address");
            $table->string("status");
            $table->string("manager");
            $table->string("feeRemitance");
            $table->string("deployer");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
