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
        Schema::create('asset_supported_chains', function (Blueprint $table) {
            $table->id();
            $table->string("asset_address");
            $table->string("chain_id");
            $table->string("supported_chain_id");
            $table->string("destination_address");
            $table->string("status");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_supported_chains');
    }
};
