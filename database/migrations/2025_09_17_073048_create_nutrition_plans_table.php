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
        Schema::create('nutrition_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('client_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('plan_name');
            $table->text('description')->nullable();
            $table->string('media_url')->nullable();
            $table->enum('status', ['active', 'inactive', 'draft'])->default('active');
            $table->boolean('is_global')->default(false); // For admin-created global plans
            $table->json('tags')->nullable(); // For categorization
            $table->integer('duration_days')->nullable(); // Plan duration
            $table->decimal('target_weight', 5, 2)->nullable();
            $table->enum('goal_type', ['weight_loss', 'weight_gain', 'maintenance', 'muscle_gain'])->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['trainer_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index(['is_global', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nutrition_plans');
    }
};
