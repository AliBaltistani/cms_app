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
        Schema::create('workout_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->onDelete('cascade');
            $table->foreignId('exercise_id')->constrained('workouts')->onDelete('cascade');
            $table->integer('order')->default(1);
            $table->integer('sets')->nullable();
            $table->integer('reps')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->integer('duration')->nullable(); // in seconds
            $table->string('rest_interval')->nullable(); // e.g., "60-90s"
            $table->string('tempo')->nullable(); // e.g., "3-1-2-1"
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['workout_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_exercises');
    }
};
