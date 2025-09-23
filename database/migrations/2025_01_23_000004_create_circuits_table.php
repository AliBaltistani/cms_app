<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Circuits Table
 * 
 * Stores circuits within workout days (Circuit 1, Circuit 2, etc.)
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
        Schema::create('circuits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('day_id')->constrained()->onDelete('cascade');
            $table->integer('circuit_number');
            $table->string('title')->nullable()->comment('Optional circuit title');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->unique(['day_id', 'circuit_number']);
            $table->index('day_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circuits');
    }
};