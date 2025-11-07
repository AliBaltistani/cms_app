<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add custom_rows column to days table
 *
 * Stores free-form custom notes/instructions rows per day as JSON array
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('days', function (Blueprint $table) {
            if (!Schema::hasColumn('days', 'custom_rows')) {
                $table->json('custom_rows')->nullable()->after('cool_down');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('days', function (Blueprint $table) {
            if (Schema::hasColumn('days', 'custom_rows')) {
                $table->dropColumn('custom_rows');
            }
        });
    }
};