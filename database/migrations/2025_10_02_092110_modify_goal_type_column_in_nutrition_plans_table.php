<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modify goal_type column in nutrition_plans table
 * 
 * Changes the goal_type column from enum to varchar to support dynamic goal types
 * from the goals table instead of hardcoded enum values.
 * 
 * @author [Your Name]
 * @since 1.0.0
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Modifies the goal_type column from enum to varchar(100) to support
     * dynamic goal types loaded from the goals table.
     */
    public function up(): void
    {
        Schema::table('nutrition_plans', function (Blueprint $table) {
            // Drop the existing enum column and recreate as varchar
            $table->dropColumn('goal_type');
        });
        
        Schema::table('nutrition_plans', function (Blueprint $table) {
            // Add the new varchar column
            $table->string('goal_type', 100)->nullable()->after('target_weight');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Reverts the goal_type column back to the original enum definition.
     */
    public function down(): void
    {
        Schema::table('nutrition_plans', function (Blueprint $table) {
            // Drop the varchar column
            $table->dropColumn('goal_type');
        });
        
        Schema::table('nutrition_plans', function (Blueprint $table) {
            // Restore the original enum column
            $table->enum('goal_type', ['weight_loss', 'weight_gain', 'maintenance', 'muscle_gain'])->nullable()->after('target_weight');
        });
    }
};
