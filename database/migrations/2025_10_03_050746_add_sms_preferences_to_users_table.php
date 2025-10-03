<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add SMS notification preferences to users table
 * 
 * Adds columns for managing SMS notification settings including
 * enabling/disabling SMS notifications and setting quiet hours
 * 
 * @package     Laravel CMS App
 * @subpackage  Database Migrations
 * @category    SMS Communication
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
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // SMS notification preferences
            $table->boolean('sms_notifications_enabled')->default(true)->after('phone');
            $table->boolean('sms_marketing_enabled')->default(false)->after('sms_notifications_enabled');
            
            // Quiet hours for SMS (24-hour format)
            $table->time('sms_quiet_start')->nullable()->after('sms_marketing_enabled');
            $table->time('sms_quiet_end')->nullable()->after('sms_quiet_start');
            
            // SMS notification types preferences
            $table->json('sms_notification_types')->nullable()->after('sms_quiet_end');
            
            // Timezone for quiet hours
            $table->string('timezone', 50)->default('UTC')->after('sms_notification_types');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * @return void
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'sms_notifications_enabled',
                'sms_marketing_enabled',
                'sms_quiet_start',
                'sms_quiet_end',
                'sms_notification_types',
                'timezone'
            ]);
        });
    }
};
