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
        Schema::create('nutrition_macros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('nutrition_plans')->onDelete('cascade');
            $table->decimal('protein', 8, 2); // in grams
            $table->decimal('carbs', 8, 2); // in grams
            $table->decimal('fats', 8, 2); // in grams
            $table->decimal('total_calories', 8, 2);
            $table->decimal('fiber', 8, 2)->nullable(); // in grams
            $table->decimal('sugar', 8, 2)->nullable(); // in grams
            $table->decimal('sodium', 8, 2)->nullable(); // in mg
            $table->decimal('water', 8, 2)->nullable(); // in liters
            $table->enum('macro_type', ['daily_target', 'meal_specific'])->default('daily_target');
            $table->foreignId('meal_id')->nullable()->constrained('nutrition_meals')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['plan_id', 'macro_type']);
            $table->index(['meal_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nutrition_macros');
    }
};
