<?php

namespace Database\Seeders;

use App\Models\Workout;
use App\Models\WorkoutVideo;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class WorkoutSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        
        $workoutTypes = [
            'cardio' => ['Running', 'Cycling', 'HIIT', 'Jump Rope', 'Dancing'],
            'strength' => ['Push Ups', 'Pull Ups', 'Squats', 'Deadlifts', 'Bench Press'],
            'yoga' => ['Vinyasa Flow', 'Hatha Yoga', 'Power Yoga', 'Restorative Yoga'],
            'flexibility' => ['Full Body Stretch', 'Hip Mobility', 'Shoulder Stretch'],
            'pilates' => ['Core Pilates', 'Full Body Pilates', 'Pilates for Beginners']
        ];
        
        // $difficulties = ['beginner', 'intermediate', 'advanced'];
        // $equipmentOptions = [
        //     ['dumbbells', 'yoga mat'],
        //     ['resistance bands', 'yoga mat'],
        //     ['kettlebells'],
        //     ['yoga mat'],
        //     ['pull-up bar', 'dumbbells'],
        //     []
        // ];

        foreach ($workoutTypes as $category => $names) {
            foreach ($names as $name) {
                $workout = Workout::create([
                    'name' => $name,
                    'duration' => $faker->numberBetween(15, 90),
                    'description' => $faker->paragraph(),
                    'is_active' => $faker->boolean(85), // 85% chance of being active
                ]);

                // Add 3-6 videos per workout
                $videoCount = $faker->numberBetween(3, 6);
                for ($i = 1; $i <= $videoCount; $i++) {
                    $videoTypes = ['youtube', 'vimeo', 'url'];
                    $videoType = $faker->randomElement($videoTypes);
                    
                    $videoUrls = [
                        'youtube' => [
                            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                            'https://www.youtube.com/watch?v=oHg5SJYRHA0',
                            'https://youtu.be/dQw4w9WgXcQ'
                        ],
                        'vimeo' => [
                            'https://vimeo.com/123456789',
                            'https://vimeo.com/987654321'
                        ],
                        'url' => [
                            'https://example.com/videos/workout1.mp4',
                            'https://example.com/videos/workout2.mp4'
                        ]
                    ];

                    WorkoutVideo::create([
                        'workout_id' => $workout->id,
                        'title' => $faker->sentence(4),
                        'description' => $faker->paragraph(),
                        'video_url' => $faker->randomElement($videoUrls[$videoType]),
                        'video_type' => $videoType,
                        'duration' => $faker->numberBetween(30, 600), // 30 seconds to 10 minutes
                        'order' => $i,
                        'is_preview' => $i === 1 && $faker->boolean(30), // 30% chance first video is preview
                        'metadata' => [
                            'resolution' => $faker->randomElement(['720p', '1080p', '4K']),
                            'file_size' => $faker->numberBetween(10, 500) . 'MB'
                        ]
                    ]);
                }
            }
        }
    }
}