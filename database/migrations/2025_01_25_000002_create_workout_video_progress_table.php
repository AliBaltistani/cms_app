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
        Schema::create('workout_video_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('workout_id')->constrained()->onDelete('cascade');
            $table->foreignId('workout_video_id')->constrained()->onDelete('cascade');
            $table->integer('watched_duration')->default(0); // in seconds
            $table->boolean('is_completed')->default(false);
            $table->timestamp('first_watched_at')->nullable();
            $table->timestamp('last_watched_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            // Prevent duplicate progress records
            $table->unique(['user_id', 'workout_video_id']);
            
            // Indexes for better performance
            $table->index(['user_id', 'workout_id']);
            $table->index(['workout_id', 'is_completed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_video_progress');
    }
};