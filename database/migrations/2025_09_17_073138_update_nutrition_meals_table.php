<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Update nutrition_meals table to match requirements
 * 
 * Updates nutrition_meals table to include media_url and proper macro fields
 * Renames image_url to media_url and ensures proper macro field names
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
        Schema::table('nutrition_meals', function (Blueprint $table) {
            // Rename image_url to media_url if it exists
            if (Schema::hasColumn('nutrition_meals', 'image_url')) {
                $table->renameColumn('image_url', 'media_url');
            } else {
                $table->string('media_url')->nullable()->after('instructions');
            }
            
            // Rename macro fields to match requirements
            if (Schema::hasColumn('nutrition_meals', 'calories_per_serving')) {
                $table->renameColumn('calories_per_serving', 'calories');
            }
            if (Schema::hasColumn('nutrition_meals', 'protein_per_serving')) {
                $table->renameColumn('protein_per_serving', 'protein');
            }
            if (Schema::hasColumn('nutrition_meals', 'carbs_per_serving')) {
                $table->renameColumn('carbs_per_serving', 'carbs');
            }
            if (Schema::hasColumn('nutrition_meals', 'fats_per_serving')) {
                $table->renameColumn('fats_per_serving', 'fats');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nutrition_meals', function (Blueprint $table) {
            // Reverse the changes
            if (Schema::hasColumn('nutrition_meals', 'media_url')) {
                $table->renameColumn('media_url', 'image_url');
            }
            
            if (Schema::hasColumn('nutrition_meals', 'calories')) {
                $table->renameColumn('calories', 'calories_per_serving');
            }
            if (Schema::hasColumn('nutrition_meals', 'protein')) {
                $table->renameColumn('protein', 'protein_per_serving');
            }
            if (Schema::hasColumn('nutrition_meals', 'carbs')) {
                $table->renameColumn('carbs', 'carbs_per_serving');
            }
            if (Schema::hasColumn('nutrition_meals', 'fats')) {
                $table->renameColumn('fats', 'fats_per_serving');
            }
        });
    }
};