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
        Schema::create('workout_exercise_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_exercise_id')->constrained()->onDelete('cascade');
            $table->integer('set_number');
            $table->integer('reps')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->integer('duration')->nullable(); // in seconds
            $table->integer('rest_time')->nullable(); // in seconds
            $table->text('notes')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['workout_exercise_id', 'set_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_exercise_sets');
    }
};
