<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE payment_gateways MODIFY COLUMN public_key TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE payment_gateways MODIFY COLUMN public_key VARCHAR(191) NULL');
    }
};

