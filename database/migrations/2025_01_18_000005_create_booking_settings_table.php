<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for creating booking_settings table
 * 
 * Handles trainer booking preferences and approval settings
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
        Schema::create('booking_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->onDelete('cascade');
            $table->boolean('allow_self_booking')->default(true);
            $table->boolean('require_approval')->default(false);
            $table->integer('advance_booking_days')->default(30);
            $table->integer('cancellation_hours')->default(24);
            $table->boolean('allow_weekend_booking')->default(true);
            $table->time('earliest_booking_time')->default('06:00:00');
            $table->time('latest_booking_time')->default('22:00:00');
            $table->timestamps();
            
            // Unique constraint for one setting per trainer
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
        Schema::dropIfExists('booking_settings');
    }
};