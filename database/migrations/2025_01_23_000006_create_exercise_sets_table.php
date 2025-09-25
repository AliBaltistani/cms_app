<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Exercise Sets Table
 * 
 * Stores sets (Set 1-5) with reps and weight for each program exercise
 * 
 * @package     Laravel CMS App
 * @subpackage  Migrations
 * @category    Workout Exercise Management
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exercise_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_exercise_id')->constrained()->onDelete('cascade');
            $table->integer('set_number');
            $table->integer('reps')->nullable()->comment('Target reps for this set');
            $table->decimal('weight', 8, 2)->nullable()->comment('Target weight for this set');
            $table->timestamps();
            
            $table->unique(['program_exercise_id', 'set_number']);
            $table->index('program_exercise_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercise_sets');
    }
};