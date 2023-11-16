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
        Schema::create('transaction_validations', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id');
            $table->string('chain_id');
            $table->string('signer');
            $table->string('signature');
            $table->string('verdict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_validations');
    }
};
