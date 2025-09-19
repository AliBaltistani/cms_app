<?php

/**
 * Specializations Seeder
 * 
 * Seeds the specializations table with default fitness specializations
 * that trainers can select from when setting up their profiles
 * 
 * @package     Laravel CMS App
 * @subpackage  Database Seeders
 * @category    Trainer Specializations
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 * @created     2025-01-19
 */

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SpecializationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Inserts default specializations into the specializations table
     * These are common fitness specializations that trainers typically offer
     */
    public function run(): void
    {
        // Default specializations data
        $specializations = [
            [
                'name' => 'Weight Loss',
                'description' => 'Specialized training programs focused on fat burning, calorie deficit management, and sustainable weight loss through effective workout routines and lifestyle modifications.',
                'status' => 1,
                'created_at' => Carbon::now(),
            ],
            [
                'name' => 'Muscle Building',
                'description' => 'Comprehensive strength training and hypertrophy programs designed to increase muscle mass, improve body composition, and enhance overall physical strength.',
                'status' => 1,
                'created_at' => Carbon::now(),
            ],
            [
                'name' => 'Strength Training',
                'description' => 'Progressive resistance training programs focused on building functional strength, power development, and improving overall physical performance.',
                'status' => 1,
                'created_at' => Carbon::now(),
            ],
            [
                'name' => 'Cardio Fitness',
                'description' => 'Cardiovascular conditioning programs including HIIT, endurance training, and aerobic exercises to improve heart health and stamina.',
                'status' => 1,
                'created_at' => Carbon::now(),
            ],
            [
                'name' => 'Yoga & Flexibility',
                'description' => 'Mind-body wellness programs combining yoga practices, stretching routines, and flexibility training for improved mobility and stress relief.',
                'status' => 1,
                'created_at' => Carbon::now(),
            ],
            [
                'name' => 'Sports Performance',
                'description' => 'Sport-specific training programs designed to enhance athletic performance, agility, speed, and sport-related skills for competitive athletes.',
                'status' => 1,
                'created_at' => Carbon::now(),
            ],
            [
                'name' => 'Functional Training',
                'description' => 'Movement-based training focusing on exercises that improve daily life activities, core stability, and functional movement patterns.',
                'status' => 1,
                'created_at' => Carbon::now(),
            ],
            [
                'name' => 'Rehabilitation & Injury Prevention',
                'description' => 'Therapeutic exercise programs for injury recovery, corrective movement patterns, and preventive training to reduce injury risk.',
                'status' => 1,
                'created_at' => Carbon::now(),
            ],
            [
                'name' => 'Senior Fitness',
                'description' => 'Age-appropriate fitness programs designed for older adults focusing on balance, mobility, strength maintenance, and fall prevention.',
                'status' => 1,
                'created_at' => Carbon::now(),
            ],
            [
                'name' => 'Prenatal & Postnatal Fitness',
                'description' => 'Specialized fitness programs for expecting and new mothers, focusing on safe exercises during pregnancy and postpartum recovery.',
                'status' => 1,
                'created_at' => Carbon::now(),
            ],
            [
                'name' => 'CrossFit Training',
                'description' => 'High-intensity functional fitness programs combining weightlifting, cardio, and gymnastics movements for overall fitness improvement.',
                'status' => 1,
                'created_at' => Carbon::now(),
            ],
            [
                'name' => 'Pilates',
                'description' => 'Core-focused exercise system emphasizing controlled movements, proper alignment, and mind-body connection for strength and flexibility.',
                'status' => 1,
                'created_at' => Carbon::now(),
            ],
            [
                'name' => 'Martial Arts & Self Defense',
                'description' => 'Training in various martial arts disciplines and self-defense techniques for fitness, discipline, and personal protection skills.',
                'status' => 1,
                'created_at' => Carbon::now(),
            ],
            [
                'name' => 'Dance Fitness',
                'description' => 'Fun and energetic fitness programs incorporating various dance styles to improve cardiovascular health, coordination, and rhythm.',
                'status' => 1,
                'created_at' => Carbon::now(),
            ],
            [
                'name' => 'Nutrition Coaching',
                'description' => 'Comprehensive nutrition guidance and meal planning services to support fitness goals and promote healthy eating habits.',
                'status' => 1,
                'created_at' => Carbon::now(),
            ],
        ];

        // Insert specializations into database
        DB::table('specializations')->insert($specializations);
        
        // Output success message
        $this->command->info('✅ Successfully seeded ' . count($specializations) . ' specializations');
        $this->command->info('📋 Specializations available for trainers to select from their profiles');
    }
}