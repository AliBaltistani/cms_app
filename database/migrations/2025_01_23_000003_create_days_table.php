<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Days Table
 * 
 * Stores workout days within program weeks (Day 1, Day 2, etc.)
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
        Schema::create('days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('week_id')->constrained()->onDelete('cascade');
            $table->integer('day_number');
            $table->string('title')->comment('e.g., Full Body Push');
            $table->text('description')->nullable();
            $table->text('cool_down')->nullable()->comment('Cool down section at end of day');
            $table->timestamps();
            
            $table->unique(['week_id', 'day_number']);
            $table->index('week_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('days');
    }
};