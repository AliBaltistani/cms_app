<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add OTP fields to password_reset_tokens table for secure password reset
     */
    public function up(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->string('otp', 6)->nullable()->after('token'); // 6-digit OTP
            $table->timestamp('otp_expires_at')->nullable()->after('otp'); // OTP expiration time
            $table->boolean('is_used')->default(false)->after('otp_expires_at'); // Track if OTP is used
            $table->integer('attempts')->default(0)->after('is_used'); // Track failed attempts
        });
    }

    /**
     * Reverse the migrations.
     * Remove OTP fields from password_reset_tokens table
     */
    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropColumn(['otp', 'otp_expires_at', 'is_used', 'attempts']);
        });
    }
};
