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
        Schema::create('nutrition_meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('nutrition_plans')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('meal_type', ['breakfast', 'lunch', 'dinner', 'snack', 'pre_workout', 'post_workout']);
            $table->text('ingredients')->nullable();
            $table->text('instructions')->nullable();
            $table->string('image_url')->nullable();
            $table->integer('prep_time')->nullable(); // in minutes
            $table->integer('cook_time')->nullable(); // in minutes
            $table->integer('servings')->default(1);
            $table->decimal('calories_per_serving', 8, 2)->nullable();
            $table->decimal('protein_per_serving', 8, 2)->nullable();
            $table->decimal('carbs_per_serving', 8, 2)->nullable();
            $table->decimal('fats_per_serving', 8, 2)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['plan_id', 'meal_type']);
            $table->index(['plan_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nutrition_meals');
    }
};
