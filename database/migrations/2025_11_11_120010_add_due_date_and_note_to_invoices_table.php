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
        Schema::table('invoices', function (Blueprint $table) {
            // Add due_date and note to align with trainer invoice creation UI
            $table->date('due_date')->nullable()->after('net_amount');
            $table->text('note')->nullable()->after('due_date');

            // Index due_date for filtering/sorting if needed
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop added columns and index
            $table->dropIndex(['due_date']);
            $table->dropColumn(['due_date', 'note']);
        });
    }
};