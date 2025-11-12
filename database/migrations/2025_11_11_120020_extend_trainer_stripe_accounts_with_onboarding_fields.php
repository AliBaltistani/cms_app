<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trainer_stripe_accounts', function (Blueprint $table) {
            // Personal identity information
            $table->string('full_name')->nullable()->after('verification_status');
            $table->date('dob')->nullable()->after('full_name');
            $table->string('address_line1')->nullable()->after('dob');
            $table->string('address_line2')->nullable()->after('address_line1');
            $table->string('city')->nullable()->after('address_line2');
            $table->string('state')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('state');
            $table->string('country')->default('US')->after('postal_code');
            $table->string('phone')->nullable()->after('country');
            $table->string('email')->nullable()->after('phone');

            // Business information
            $table->string('business_name')->nullable()->after('email');
            $table->string('business_type')->nullable()->after('business_name'); // e.g., individual, sole_proprietorship, company
            $table->string('business_address_line1')->nullable()->after('business_type');
            $table->string('business_address_line2')->nullable()->after('business_address_line1');
            $table->string('business_city')->nullable()->after('business_address_line2');
            $table->string('business_state')->nullable()->after('business_city');
            $table->string('business_postal_code')->nullable()->after('business_state');
            $table->string('business_country')->default('US')->after('business_postal_code');
            $table->string('business_phone')->nullable()->after('business_country');
            $table->string('business_email')->nullable()->after('business_phone');
            $table->string('tax_id_last4')->nullable()->after('business_email');

            // Bank account information (store non-sensitive hints only)
            $table->string('account_holder_name')->nullable()->after('tax_id_last4');
            $table->string('bank_name')->nullable()->after('account_holder_name');
            $table->string('bank_account_last4', 4)->nullable()->after('bank_name');
            $table->string('routing_number_last4', 4)->nullable()->after('bank_account_last4');
            $table->string('external_account_id')->nullable()->after('routing_number_last4'); // Stripe external bank account ID
            $table->string('bank_verification_status')->default('pending')->after('external_account_id'); // pending|verified|failed
            $table->timestamp('bank_added_at')->nullable()->after('bank_verification_status');

            // Review payload (for UI confirmation snapshot)
            $table->json('onboarding_review')->nullable()->after('bank_added_at');
            $table->timestamp('details_submitted_at')->nullable()->after('onboarding_review');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trainer_stripe_accounts', function (Blueprint $table) {
            $table->dropColumn([
                // Identity
                'full_name', 'dob', 'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'country', 'phone', 'email',
                // Business
                'business_name', 'business_type', 'business_address_line1', 'business_address_line2', 'business_city', 'business_state', 'business_postal_code', 'business_country', 'business_phone', 'business_email', 'tax_id_last4',
                // Bank
                'account_holder_name', 'bank_name', 'bank_account_last4', 'routing_number_last4', 'external_account_id', 'bank_verification_status', 'bank_added_at',
                // Review
                'onboarding_review', 'details_submitted_at',
            ]);
        });
    }
};