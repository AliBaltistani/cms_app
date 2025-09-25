<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Update nutrition_plans table to match requirements
 * 
 * Updates nutrition_plans table to rename media_url to image_url as per requirements
 * 
 * @package Database\Migrations
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nutrition_plans', function (Blueprint $table) {
            // Rename media_url to image_url to match requirements
            if (Schema::hasColumn('nutrition_plans', 'media_url')) {
                $table->renameColumn('media_url', 'image_url');
            } else {
                $table->string('image_url')->nullable()->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nutrition_plans', function (Blueprint $table) {
            // Reverse the change
            if (Schema::hasColumn('nutrition_plans', 'image_url')) {
                $table->renameColumn('image_url', 'media_url');
            }
        });
    }
};