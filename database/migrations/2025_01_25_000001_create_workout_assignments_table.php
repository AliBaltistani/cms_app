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
        Schema::create('workout_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
            $table->enum('assigned_to_type', ['trainer', 'client']);
            $table->timestamp('assigned_at');
            $table->timestamp('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['assigned', 'in_progress', 'completed', 'cancelled'])->default('assigned');
            $table->timestamps();
            
            // Prevent duplicate assignments
            $table->unique(['workout_id', 'assigned_to']);
            
            // Indexes for better performance
            $table->index(['assigned_to', 'assigned_to_type']);
            $table->index(['workout_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_assignments');
    }
};