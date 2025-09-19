<?php

/**
 * Migration for creating specializations table
 * 
 * This table stores default trainer specializations that can be managed by Admin
 * Trainers can only select from these predefined specializations
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
     * Creates the specializations table with the following structure:
     * - id: Primary key (auto increment)
     * - name: Specialization name (required, max 100 chars)
     * - description: Optional description text
     * - status: Active/Inactive flag (1=active, 0=inactive)
     * - created_at: Timestamp of creation
     */
    public function up(): void
    {
        Schema::create('specializations', function (Blueprint $table) {
            $table->id(); // INT AUTO_INCREMENT PRIMARY KEY
            $table->string('name', 100)->nullable(false); // VARCHAR(100) NOT NULL
            $table->text('description')->nullable(); // TEXT NULL
            $table->tinyInteger('status')->default(1); // TINYINT(1) DEFAULT 1 (1 = active, 0 = inactive)
            $table->timestamp('created_at')->useCurrent(); // TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            
            // Add indexes for better performance
            $table->index('status');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Drops the specializations table
     */
    public function down(): void
    {
        Schema::dropIfExists('specializations');
    }
};