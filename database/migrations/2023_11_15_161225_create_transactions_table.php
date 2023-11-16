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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string("transaction_id");
            $table->string("transaction_hash")->nullable();
            $table->string("anouncement_hash")->nullable();
            $table->string("destination_hash")->nullable();
            $table->string("chain_id");
            $table->string("interfacing_chain_id");
            $table->string("asset_address");
            $table->string("asset_id");
            $table->string("nounce");
            $table->string("reciever");
            $table->string("sender")->nullable();;
            $table->string("type");
            $table->string("status");
            $table->date('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
