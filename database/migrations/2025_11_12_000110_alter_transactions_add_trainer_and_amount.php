<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('trainer_id')->nullable()->after('client_id');
            $table->decimal('amount', 10, 2)->nullable()->after('payment_method');
            $table->index('trainer_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['trainer_id']);
            $table->dropColumn(['trainer_id', 'amount']);
        });
    }
};

