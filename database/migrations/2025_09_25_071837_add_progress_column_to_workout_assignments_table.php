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
        Schema::table('workout_assignments', function (Blueprint $table) {
            // Add progress column as decimal with 2 decimal places, default 0.00
            $table->decimal('progress', 5, 2)->default(0.00)->after('status');
            // Add completed_at timestamp column for tracking completion time
            $table->timestamp('completed_at')->nullable()->after('progress');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workout_assignments', function (Blueprint $table) {
            // Drop the added columns in reverse order
            $table->dropColumn(['completed_at', 'progress']);
        });
    }
};
