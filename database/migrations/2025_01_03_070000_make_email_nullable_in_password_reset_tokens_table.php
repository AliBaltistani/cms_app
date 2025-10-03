<?php

/**
 * Migration to restructure password_reset_tokens table for phone support
 * 
 * This fixes the issue where phone-based password reset fails because
 * the email field is the primary key and cannot be nullable.
 * We need to add an auto-increment ID as primary key and make email nullable.
 * 
 * @package     Laravel CMS App
 * @subpackage  Database Migrations
 * @category    Password Reset
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 * @created     2025-01-03
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Restructures the password_reset_tokens table to support phone-based resets:
     * 1. Adds auto-increment ID as primary key
     * 2. Makes email field nullable
     * 3. Adds unique constraint for email when not null
     * 
     * @return void
     */
    public function up(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            // First, drop the primary key constraint on email
            $table->dropPrimary(['email']);
            
            // Add auto-increment ID as new primary key
            $table->id()->first();
            
            // Make email nullable and add unique constraint for non-null values
            $table->string('email')->nullable()->change();
            
            // Add unique index for email when it's not null
            $table->unique('email', 'password_reset_tokens_email_unique');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Reverts the table structure back to original state
     * Note: This will fail if there are records with null email values
     * 
     * @return void
     */
    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            // Drop the unique constraint on email
            $table->dropUnique('password_reset_tokens_email_unique');
            
            // Drop the auto-increment ID column
            $table->dropColumn('id');
            
            // Make email not nullable and set as primary key
            $table->string('email')->nullable(false)->change();
            $table->primary('email');
        });
    }
};