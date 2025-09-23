<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Workout;
use App\Models\Program;
use App\Models\Week;
use App\Models\Day;
use App\Models\Circuit;
use App\Models\ProgramExercise;
use App\Models\ExerciseSet;

/**
 * Program Builder Seeder
 * 
 * Creates sample data for testing the program builder functionality
 * 
 * @package     Laravel CMS
 * @subpackage  Seeders
 * @category    Program Builder
 * @author      TRAE.AI Assistant
 * @since       1.0.0
 */
class ProgramBuilderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create sample users if they don't exist
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'phone' => '+92 300 0000000',
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $trainer = User::firstOrCreate(
            ['email' => 'trainer@example.com'],
            [
                'name' => 'John Trainer',
                'password' => bcrypt('password'),
                'phone' => '+92 300 1234567',
                'role' => 'trainer',
                'designation' => 'Senior Fitness Trainer',
                'experience' => '5_years',
                'about' => 'Experienced trainer specializing in strength training and functional fitness.',
                'training_philosophy' => 'Focus on proper form, progressive overload, and sustainable habits.',
                'email_verified_at' => now(),
            ]
        );

        $client = User::firstOrCreate(
            ['email' => 'client@example.com'],
            [
                'name' => 'Jane Client',
                'password' => bcrypt('password'),
                'phone' => '+92 300 7654321',
                'role' => 'client',
                'email_verified_at' => now(),
            ]
        );

        // Create sample workouts
        $workouts = [];
        $workoutNames = [
            'Paloff Press',
            'Hamstring Curls', 
            'Incline DB Chest Press',
            'Squats',
            'Lat Pull Down (Neutral Grip)',
            'Tube Walks'
        ];

        foreach ($workoutNames as $name) {
            $workouts[] = Workout::create([
                'name' => $name,
                'duration' => rand(15, 45),
                'description' => "Sample workout: {$name}",
                'is_active' => true,
                'user_id' => $trainer->id,
                'price' => 0.00
            ]);
        }

        // Get created workouts
        $pushups = Workout::where('name', 'Push-ups')->first();
        $squats = Workout::where('name', 'Squats')->first();
        $plank = Workout::where('name', 'Plank')->first();
        $burpees = Workout::where('name', 'Burpees')->first();
        $mountainClimbers = Workout::where('name', 'Mountain Climbers')->first();

        // Create a sample program
        $program = Program::create([
            'trainer_id' => $trainer->id,
            'client_id' => $client->id,
            'name' => 'Beginner Full Body Program',
            'duration' => 4, // 4 weeks
            'description' => 'A comprehensive 4-week program designed for beginners to build strength and endurance',
            'is_active' => true
        ]);

        // Create Week 1
        $week1 = Week::firstOrCreate(
            ['program_id' => $program->id, 'week_number' => 1],
            [
                'program_id' => $program->id,
                'week_number' => 1,
                'title' => 'Foundation Week',
                'description' => 'Building basic movement patterns and strength foundation',
            ]
        );

        // Create Day 1 of Week 1
        $day1 = Day::firstOrCreate(
            ['week_id' => $week1->id, 'day_number' => 1],
            [
                'week_id' => $week1->id,
                'day_number' => 1,
                'title' => 'Full Body Push',
                'description' => 'Focus on pushing movements and core stability',
                'cool_down' => '5-minute walk and light stretching focusing on chest, shoulders, and core',
            ]
        );

        // Create circuits for Day 1
        $circuit1 = Circuit::firstOrCreate(
            ['day_id' => $day1->id, 'circuit_number' => 1],
            [
                'day_id' => $day1->id,
                'circuit_number' => 1,
                'title' => 'Warm-up Circuit',
                'description' => 'Prepare the body for the main workout',
            ]
        );

        $circuit2 = Circuit::firstOrCreate(
            ['day_id' => $day1->id, 'circuit_number' => 2],
            [
                'day_id' => $day1->id,
                'circuit_number' => 2,
                'title' => 'Main Strength Circuit',
                'description' => 'Primary strength building exercises',
            ]
        );

        // Add exercises to circuits
        if ($pushups && $circuit1) {
            $programExercise1 = ProgramExercise::firstOrCreate(
                ['circuit_id' => $circuit1->id, 'workout_id' => $pushups->id],
                [
                    'circuit_id' => $circuit1->id,
                    'workout_id' => $pushups->id,
                    'order' => 1,
                    'tempo' => '2-1-2-1',
                    'rest_interval' => '30-45s',
                    'notes' => 'Modify on knees if needed',
                ]
            );

            // Create exercise sets for push-ups
            $sets = [
                ['set_number' => 1, 'reps' => 8, 'weight' => null],
                ['set_number' => 2, 'reps' => 8, 'weight' => null],
                ['set_number' => 3, 'reps' => 6, 'weight' => null],
            ];

            foreach ($sets as $setData) {
                ExerciseSet::firstOrCreate(
                    [
                        'program_exercise_id' => $programExercise1->id,
                        'set_number' => $setData['set_number']
                    ],
                    array_merge(['program_exercise_id' => $programExercise1->id], $setData)
                );
            }
        }

        if ($squats && $circuit2) {
            $programExercise2 = ProgramExercise::firstOrCreate(
                ['circuit_id' => $circuit2->id, 'workout_id' => $squats->id],
                [
                    'circuit_id' => $circuit2->id,
                    'workout_id' => $squats->id,
                    'order' => 1,
                    'tempo' => '3-1-2-1',
                    'rest_interval' => '45-60s',
                    'notes' => 'Focus on proper form over speed',
                ]
            );

            // Create exercise sets for squats
            $sets = [
                ['set_number' => 1, 'reps' => 12, 'weight' => null],
                ['set_number' => 2, 'reps' => 12, 'weight' => null],
                ['set_number' => 3, 'reps' => 10, 'weight' => null],
                ['set_number' => 4, 'reps' => 8, 'weight' => null],
            ];

            foreach ($sets as $setData) {
                ExerciseSet::firstOrCreate(
                    [
                        'program_exercise_id' => $programExercise2->id,
                        'set_number' => $setData['set_number']
                    ],
                    array_merge(['program_exercise_id' => $programExercise2->id], $setData)
                );
            }
        }

        if ($plank && $circuit2) {
            $programExercise3 = ProgramExercise::firstOrCreate(
                ['circuit_id' => $circuit2->id, 'workout_id' => $plank->id],
                [
                    'circuit_id' => $circuit2->id,
                    'workout_id' => $plank->id,
                    'order' => 2,
                    'tempo' => 'Hold',
                    'rest_interval' => '60s',
                    'notes' => 'Hold for specified time, maintain straight line',
                ]
            );

            // Create exercise sets for plank (time-based)
            $sets = [
                ['set_number' => 1, 'reps' => 30, 'weight' => null], // 30 seconds
                ['set_number' => 2, 'reps' => 30, 'weight' => null], // 30 seconds
                ['set_number' => 3, 'reps' => 20, 'weight' => null], // 20 seconds
            ];

            foreach ($sets as $setData) {
                ExerciseSet::firstOrCreate(
                    [
                        'program_exercise_id' => $programExercise3->id,
                        'set_number' => $setData['set_number']
                    ],
                    array_merge(['program_exercise_id' => $programExercise3->id], $setData)
                );
            }
        }

        // Create Day 2 of Week 1
        $day2 = Day::firstOrCreate(
            ['week_id' => $week1->id, 'day_number' => 2],
            [
                'week_id' => $week1->id,
                'day_number' => 2,
                'title' => 'Active Recovery',
                'description' => 'Light movement and mobility work',
                'cool_down' => '10-minute gentle stretching and breathing exercises',
            ]
        );

        // Add a simple circuit for active recovery
        $recoveryCircuit = Circuit::firstOrCreate(
            ['day_id' => $day2->id, 'circuit_number' => 1],
            [
                'day_id' => $day2->id,
                'circuit_number' => 1,
                'title' => 'Mobility Circuit',
                'description' => 'Gentle movements for recovery',
            ]
        );

        if ($mountainClimbers && $recoveryCircuit) {
            $programExercise4 = ProgramExercise::firstOrCreate(
                ['circuit_id' => $recoveryCircuit->id, 'workout_id' => $mountainClimbers->id],
                [
                    'circuit_id' => $recoveryCircuit->id,
                    'workout_id' => $mountainClimbers->id,
                    'order' => 1,
                    'tempo' => 'Controlled',
                    'rest_interval' => '90s',
                    'notes' => 'Slow and controlled movement',
                ]
            );

            // Create exercise sets for mountain climbers
            $sets = [
                ['set_number' => 1, 'reps' => 10, 'weight' => null],
                ['set_number' => 2, 'reps' => 10, 'weight' => null],
            ];

            foreach ($sets as $setData) {
                ExerciseSet::firstOrCreate(
                    [
                        'program_exercise_id' => $programExercise4->id,
                        'set_number' => $setData['set_number']
                    ],
                    array_merge(['program_exercise_id' => $programExercise4->id], $setData)
                );
            }
        }

        $this->command->info('Program Builder sample data created successfully!');
        $this->command->info('Created:');
        $this->command->info('- 1 Program: ' . $program->name);
        $this->command->info('- 1 Week: ' . $week1->title);
        $this->command->info('- 2 Days: ' . $day1->title . ', ' . $day2->title);
        $this->command->info('- 3 Circuits with exercises and sets');
        $this->command->info('- Sample trainer and client users');
        $this->command->info('- 5 Sample workout exercises');
    }
}