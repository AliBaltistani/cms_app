<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * User Locations Migration
 * 
 * Creates the user_locations table to store location information
 * for all user types (admin, trainer, trainee)
 * 
 * @package     Go Globe CMS
 * @subpackage  Migrations
 * @category    User Management
 * @author      System Administrator
 * @since       1.0.0
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates user_locations table with foreign key relationship to users table
     * 
     * @return void
     */
    public function up(): void
    {
        Schema::create('user_locations', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Foreign key to users table
            $table->unsignedBigInteger('user_id');
            
            // Location fields
            $table->string('country', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('zipcode', 20)->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
                  
            // Index for better query performance
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Drops the user_locations table
     * 
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('user_locations');
    }
};