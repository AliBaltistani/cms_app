<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Price Column to Workouts Table
 * 
 * Adds a price column to store workout pricing information
 * 
 * @package     Laravel CMS App
 * @subpackage  Migrations
 * @category    Workout Management
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds price column after name column with default value of 0.00
     * 
     * @return void
     */
    public function up(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            // Add price column after name column
            $table->decimal('price', 10, 2)->default(0.00)->after('name');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Removes the price column from workouts table
     * 
     * @return void
     */
    public function down(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            // Drop the price column
            $table->dropColumn('price');
        });
    }
};
