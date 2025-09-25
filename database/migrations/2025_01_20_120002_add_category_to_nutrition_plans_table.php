<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add category field to nutrition_plans table
 * 
 * Adds category field for plan categorization (Weight Loss, Muscle Gain, Wellness, etc.)
 * 
 * @package Database\Migrations
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nutrition_plans', function (Blueprint $table) {
            $table->string('category')->nullable()->after('goal_type');
            
            // Add index for category filtering
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nutrition_plans', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }
};