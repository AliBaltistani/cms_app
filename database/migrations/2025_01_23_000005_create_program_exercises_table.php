<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Program Exercises Table
 * 
 * Links exercises from workout library to circuits with tempo, rest, and notes
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
        Schema::create('program_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circuit_id')->constrained()->onDelete('cascade');
            $table->foreignId('workout_id')->constrained()->onDelete('cascade');
            $table->integer('order')->default(0)->comment('Exercise order within circuit');
            $table->string('tempo')->nullable()->comment('Exercise tempo (e.g., 2-1-2-1)');
            $table->string('rest_interval')->nullable()->comment('Rest time between sets');
            $table->text('notes')->nullable()->comment('Exercise-specific notes');
            $table->timestamps();
            
            $table->index(['circuit_id', 'order']);
            $table->index('workout_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_exercises');
    }
};