<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for creating blocked_times table
 * 
 * Handles trainer blocked time slots with reasons
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
        Schema::create('blocked_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('reason')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurring_type', ['daily', 'weekly', 'monthly'])->nullable();
            $table->date('recurring_end_date')->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['trainer_id', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('blocked_times');
    }
};