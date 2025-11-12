<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trainer_id');
            $table->string('gateway')->default('stripe');
            $table->string('account_id');
            $table->string('display_name')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('verification_status')->default('pending');
            $table->timestamp('last_status_sync_at')->nullable();
            $table->json('raw_meta')->nullable();
            $table->timestamps();

            $table->foreign('trainer_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['trainer_id', 'gateway']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_bank_accounts');
    }
};

