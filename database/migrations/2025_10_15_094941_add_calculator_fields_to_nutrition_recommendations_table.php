<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add calculator fields to nutrition_recommendations table
 * 
 * Adds BMR, TDEE, activity level, calculation method, and macro distribution
 * fields to support the nutrition calculator functionality
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
        Schema::table('nutrition_recommendations', function (Blueprint $table) {
            // Add BMR and TDEE calculation fields
            $table->decimal('bmr', 8, 2)->nullable()->after('fats')->comment('Basal Metabolic Rate');
            $table->decimal('tdee', 8, 2)->nullable()->after('bmr')->comment('Total Daily Energy Expenditure');
            
            // Add activity level for calculation reference
            $table->string('activity_level')->nullable()->after('tdee')->comment('Activity level used in calculation');
            
            // Add calculation method for tracking
            $table->string('calculation_method')->default('mifflin_st_jeor')->after('activity_level')->comment('Formula used for BMR calculation');
            
            // Add macro distribution percentages as JSON
            $table->json('macro_distribution')->nullable()->after('calculation_method')->comment('Macro percentage distribution');
            
            // Add indexes for better performance
            $table->index('activity_level');
            $table->index('calculation_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nutrition_recommendations', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['activity_level']);
            $table->dropIndex(['calculation_method']);
            
            // Drop columns
            $table->dropColumn([
                'bmr',
                'tdee', 
                'activity_level',
                'calculation_method',
                'macro_distribution'
            ]);
        });
    }
};
