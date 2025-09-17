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
        Schema::create('nutrition_restrictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('nutrition_plans')->onDelete('cascade');
            
            // Dietary preferences
            $table->boolean('vegetarian')->default(false);
            $table->boolean('vegan')->default(false);
            $table->boolean('pescatarian')->default(false);
            $table->boolean('keto')->default(false);
            $table->boolean('paleo')->default(false);
            $table->boolean('mediterranean')->default(false);
            $table->boolean('low_carb')->default(false);
            $table->boolean('low_fat')->default(false);
            $table->boolean('high_protein')->default(false);
            
            // Allergens and intolerances
            $table->boolean('gluten_free')->default(false);
            $table->boolean('dairy_free')->default(false);
            $table->boolean('nut_free')->default(false);
            $table->boolean('soy_free')->default(false);
            $table->boolean('egg_free')->default(false);
            $table->boolean('shellfish_free')->default(false);
            $table->boolean('fish_free')->default(false);
            $table->boolean('sesame_free')->default(false);
            
            // Medical restrictions
            $table->boolean('diabetic_friendly')->default(false);
            $table->boolean('heart_healthy')->default(false);
            $table->boolean('low_sodium')->default(false);
            $table->boolean('low_sugar')->default(false);
            
            // Custom restrictions
            $table->json('custom_restrictions')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Index for better performance
            $table->index(['plan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nutrition_restrictions');
    }
};
