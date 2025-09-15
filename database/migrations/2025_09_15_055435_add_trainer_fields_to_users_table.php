<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add trainer-specific fields to users table
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('designation')->nullable()->after('role');
            $table->enum('experience', [
                'less_than_1_year',
                '1_year',
                '2_years', 
                '3_years',
                '4_years',
                '5_years',
                '6_years',
                '7_years',
                '8_years',
                '9_years',
                '10_years',
                'more_than_10_years'
            ])->nullable()->after('designation');
            $table->text('about')->nullable()->after('experience');
            $table->text('training_philosophy')->nullable()->after('about');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Remove trainer-specific fields from users table
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'designation',
                'experience', 
                'about',
                'training_philosophy'
            ]);
        });
    }
};
