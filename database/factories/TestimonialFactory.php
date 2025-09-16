<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Testimonial>
 */
class TestimonialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $testimonialComments = [
            'Amazing trainer! Really helped me achieve my fitness goals. The workouts were challenging but effective.',
            'Professional and knowledgeable. I learned so much about proper form and nutrition.',
            'Great motivator and very patient. Made working out enjoyable and sustainable.',
            'Excellent trainer with a personalized approach. Saw results within the first month.',
            'Very supportive and understanding of my limitations. Helped me build confidence.',
            'Outstanding knowledge of fitness and nutrition. Highly recommend to anyone serious about their health.',
            'Fantastic trainer who really cares about client success. Always available for questions.',
            'Transformed my approach to fitness completely. Professional and results-driven.',
            'Great at explaining exercises and ensuring proper technique. Very safety-conscious.',
            'Inspiring and motivational. Made me believe I could achieve goals I never thought possible.',
            'Excellent communication skills and very reliable. Always prepared for sessions.',
            'Helped me overcome my fear of the gym. Very encouraging and supportive.',
            'Knowledgeable about different training methods. Kept workouts interesting and varied.',
            'Professional demeanor and great results. Would definitely train with again.',
            'Very patient with beginners and great at building progressive programs.'
        ];
        
        return [
            'trainer_id' => User::factory(),
            'client_id' => User::factory(),
            'name' => $this->faker->name(),
            'date' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'rate' => $this->faker->numberBetween(3, 5), // Most testimonials are positive
            'comments' => $this->faker->randomElement($testimonialComments),
            'likes' => $this->faker->numberBetween(0, 25),
            'dislikes' => $this->faker->numberBetween(0, 5)
        ];
    }
}
