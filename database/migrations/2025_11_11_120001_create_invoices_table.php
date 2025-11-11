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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trainer_id');
            $table->unsignedBigInteger('client_id');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('status', ['draft', 'unpaid', 'paid', 'failed'])->default('unpaid');
            $table->enum('payment_method', ['stripe', 'paypal'])->nullable();
            $table->string('transaction_id')->nullable();
            $table->decimal('commission_amount', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['trainer_id', 'client_id']);
            $table->foreign('trainer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};