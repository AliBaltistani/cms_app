<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Client Progress Table
 * 
 * Tracks client progress on program exercises including logged reps and weight
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
        Schema::create('client_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('program_exercise_id')->constrained()->onDelete('cascade');
            $table->integer('set_number');
            $table->enum('status', ['completed', 'skipped'])->default('completed');
            $table->integer('logged_reps')->nullable()->comment('Actual reps performed');
            $table->decimal('logged_weight', 8, 2)->nullable()->comment('Actual weight used');
            $table->text('notes')->nullable()->comment('Client notes for this set');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['client_id', 'program_exercise_id', 'set_number']);
            $table->index(['client_id', 'completed_at']);
            $table->index('program_exercise_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_progress');
    }
};