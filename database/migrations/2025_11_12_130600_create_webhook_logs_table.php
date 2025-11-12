<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gateway_id')->nullable();
            $table->string('event_type')->nullable();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->string('status')->default('received');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('gateway_id')->references('id')->on('payment_gateways')->onDelete('set null');
            $table->index(['gateway_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};

