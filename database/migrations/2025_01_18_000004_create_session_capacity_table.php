<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for creating session_capacity table
 * 
 * Handles trainer session capacity limits (daily/weekly)
 * 
 * @package     Laravel CMS App
 * @subpackage  Migrations
 * @category    Scheduling Module
 * @author      Go Globe CMS Team
 * @since       1.0.0
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * @return void
     */
    public function up()
    {
        Schema::create('session_capacity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->onDelete('cascade');
            $table->integer('max_daily_sessions')->default(8);
            $table->integer('max_weekly_sessions')->default(40);
            $table->integer('session_duration_minutes')->default(60);
            $table->integer('break_between_sessions_minutes')->default(15);
            $table->timestamps();
            
            // Unique constraint for one capacity setting per trainer
            $table->unique('trainer_id');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('session_capacity');
    }
};