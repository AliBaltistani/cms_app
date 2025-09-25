<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create food_diary table
 * 
 * Stores client food diary entries for tracking daily meals
 * Allows clients to log meals and track nutritional intake
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
        Schema::create('food_diary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('meal_id')->nullable()->constrained('nutrition_meals')->onDelete('set null');
            $table->string('meal_name');
            $table->decimal('calories', 8, 2)->default(0);
            $table->decimal('protein', 8, 2)->default(0);
            $table->decimal('carbs', 8, 2)->default(0);
            $table->decimal('fats', 8, 2)->default(0);
            $table->timestamp('logged_at');
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['client_id', 'logged_at']);
            $table->index('meal_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_diary');
    }
};