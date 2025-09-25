<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Programs Table
 * 
 * Stores workout programs that can be assigned to clients by trainers
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
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->integer('duration')->comment('Duration in weeks');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['trainer_id', 'client_id']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};