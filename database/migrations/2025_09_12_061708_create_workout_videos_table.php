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
        Schema::create('workout_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('video_url'); // Can be YouTube URL, local file path, or external URL
            $table->string('video_type')->default('url'); // url, file, youtube, vimeo
            $table->string('thumbnail')->nullable();
            $table->integer('duration')->nullable(); // Duration in seconds
            $table->integer('order')->default(0); // Order of video in workout
            $table->boolean('is_preview')->default(false); // Is this a preview video?
            $table->json('metadata')->nullable(); // Additional video metadata
            $table->timestamps();
            
            $table->index(['workout_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_videos');
    }
};
