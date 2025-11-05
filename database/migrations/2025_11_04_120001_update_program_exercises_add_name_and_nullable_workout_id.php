<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Update Program Exercises Table
 *
 * - Make workout_id nullable to allow free-form exercises
 * - Add name column for independent exercise titles
 *
 * @package     Laravel CMS App
 * @subpackage  Migrations
 * @category    Workout Exercise Management
 * @author      TRAE Assistant
 * @since       1.1.0
 * @version     1.1.0
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('program_exercises', function (Blueprint $table) {
            // Add a name column for free-form exercise titles
            $table->string('name')->nullable()->after('workout_id')->comment('Free-form exercise name when not linked to a workout');

            // Make workout_id nullable so exercises can be independent
            $table->foreignId('workout_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_exercises', function (Blueprint $table) {
            // Revert workout_id back to not nullable
            $table->foreignId('workout_id')->nullable(false)->change();

            // Drop the name column
            $table->dropColumn('name');
        });
    }
};