<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create nutrition_recommendations table
 * 
 * Stores macronutrient recommendations for nutrition plans
 * Allows trainers to set specific macro targets for clients
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
        Schema::create('nutrition_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('nutrition_plans')->onDelete('cascade');
            $table->decimal('target_calories', 8, 2)->default(0);
            $table->decimal('protein', 8, 2)->default(0);
            $table->decimal('carbs', 8, 2)->default(0);
            $table->decimal('fats', 8, 2)->default(0);
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nutrition_recommendations');
    }
};