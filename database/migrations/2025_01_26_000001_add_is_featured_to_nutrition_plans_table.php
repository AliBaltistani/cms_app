<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add is_featured column to nutrition_plans table
 * 
 * This migration adds a boolean column to track featured nutrition plans
 * Featured plans can be highlighted in the UI for better visibility
 * 
 * @package Database\Migrations
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * @return void
     */
    public function up(): void
    {
        Schema::table('nutrition_plans', function (Blueprint $table) {
            // Add is_featured column with default value false
            $table->boolean('is_featured')->default(false)->after('is_global');
            
            // Add index for better query performance when filtering featured plans
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * @return void
     */
    public function down(): void
    {
        Schema::table('nutrition_plans', function (Blueprint $table) {
            // Drop the index first
            $table->dropIndex(['is_featured']);
            
            // Drop the is_featured column
            $table->dropColumn('is_featured');
        });
    }
};