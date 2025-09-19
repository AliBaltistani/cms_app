<?php

/**
 * Migration for creating trainer_specializations table
 * 
 * This is a pivot table that links trainers with their specializations
 * Establishes many-to-many relationship between users (trainers) and specializations
 * 
 * @package     Laravel CMS App
 * @subpackage  Database Migrations
 * @category    Trainer Specializations
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 * @created     2025-01-19
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the trainer_specializations pivot table with the following structure:
     * - id: Primary key (auto increment)
     * - trainer_id: Foreign key to users.id
     * - specialization_id: Foreign key to specializations.id
     * - created_at: Timestamp of creation
     * - Foreign key constraints with CASCADE delete
     */
    public function up(): void
    {
        Schema::create('trainer_specializations', function (Blueprint $table) {
            $table->id(); // INT AUTO_INCREMENT PRIMARY KEY
            $table->unsignedBigInteger('trainer_id'); // INT NOT NULL - FK to users.id
            $table->unsignedBigInteger('specialization_id'); // INT NOT NULL - FK to specializations.id
            $table->timestamp('created_at')->useCurrent(); // TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            
            // Foreign key constraints
            $table->foreign('trainer_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade'); // ON DELETE CASCADE
                  
            $table->foreign('specialization_id')
                  ->references('id')
                  ->on('specializations')
                  ->onDelete('cascade'); // ON DELETE CASCADE
            
            // Unique constraint to prevent duplicate trainer-specialization pairs
            $table->unique(['trainer_id', 'specialization_id'], 'trainer_specialization_unique');
            
            // Add indexes for better performance
            $table->index('trainer_id');
            $table->index('specialization_id');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Drops the trainer_specializations table
     */
    public function down(): void
    {
        Schema::dropIfExists('trainer_specializations');
    }
};