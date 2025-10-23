<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for adding Google Calendar integration fields to schedules table
 * 
 * Adds google_event_id and meet_link fields to store Google Calendar event data
 * 
 * @package     Laravel CMS App
 * @subpackage  Migrations
 * @category    Google Calendar Integration
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
        Schema::table('schedules', function (Blueprint $table) {
            $table->string('google_event_id')->nullable()->after('notes');
            $table->string('meet_link')->nullable()->after('google_event_id');
            
            // Add index for google_event_id for better performance
            $table->index('google_event_id');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * @return void
     */
    public function down()
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex(['google_event_id']);
            $table->dropColumn(['google_event_id', 'meet_link']);
        });
    }
};