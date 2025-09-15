<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Create testimonials table for trainer reviews
     */
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->string('name'); // Client name
            $table->date('date');
            $table->integer('rate')->unsigned()->default(1); // Rating 1-5
            $table->text('comments');
            $table->integer('likes')->unsigned()->default(0);
            $table->integer('dislikes')->unsigned()->default(0);
            $table->timestamps();
            
            // Add indexes for better query performance
            $table->index('trainer_id');
            $table->index('client_id');
            $table->index('rate');
            
            // Note: Rate validation (1-5) will be handled in the model/request validation
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
