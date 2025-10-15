<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create nutrition_recipes table
 * 
 * Simplified recipe management for nutrition plans
 * Contains only basic recipe information: image, title, description
 * 
 * @package Go Globe CMS
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
        Schema::create('nutrition_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('nutrition_plans')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['plan_id', 'sort_order']);
            $table->index('plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nutrition_recipes');
    }
};