<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trainer_id');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('fee_amount', 12, 2)->default(0);
            $table->enum('payout_status', ['processing', 'completed', 'failed'])->default('processing');
            $table->string('gateway_payout_id')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();

            $table->foreign('trainer_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['trainer_id', 'payout_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};

