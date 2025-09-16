<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Create testimonial_likes_dislikes table for user reactions
     */
    public function up(): void
    {
        Schema::create('testimonial_likes_dislikes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('testimonial_id')->constrained('testimonials')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('like')->default(false);
            $table->boolean('dislike')->default(false);
            $table->timestamps();
            
            // Ensure one user can only react once per testimonial
            $table->unique(['testimonial_id', 'user_id']);
            
            // Add indexes for better query performance
            $table->index('testimonial_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testimonial_likes_dislikes');
    }
};
