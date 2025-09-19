<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add missing certification fields to user_certifications table
     */
    public function up(): void
    {
        Schema::table('user_certifications', function (Blueprint $table) {
            // Add the missing fields that the form expects (name and issuing_organization already exist)
            $table->date('issue_date')->nullable()->after('issuing_organization'); // Date when certification was issued
            $table->date('expiry_date')->nullable()->after('issue_date'); // Expiry date (optional)
            $table->string('credential_id')->nullable()->after('expiry_date'); // Credential ID (optional)
            $table->string('credential_url', 500)->nullable()->after('credential_id'); // Credential URL (optional)
            
            // Keep the existing certificate_name field for backward compatibility
            // but make it nullable since we're now using 'name' field
            $table->string('certificate_name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_certifications', function (Blueprint $table) {
            // Remove the added fields
            $table->dropColumn([
                'issue_date',
                'expiry_date',
                'credential_id',
                'credential_url'
            ]);
            
            // Restore certificate_name to not nullable
            $table->string('certificate_name')->nullable(false)->change();
        });
    }
};