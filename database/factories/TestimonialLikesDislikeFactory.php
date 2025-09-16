<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Testimonial;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TestimonialLikesDislike>
 */
class TestimonialLikesDislikeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 80% chance of like, 20% chance of dislike
        $isLike = $this->faker->boolean(80);
        
        return [
            'testimonial_id' => Testimonial::factory(),
            'user_id' => User::factory(),
            'like' => $isLike,
            'dislike' => !$isLike
        ];
    }
    
    /**
     * Create a like reaction.
     */
    public function like(): static
    {
        return $this->state(fn (array $attributes) => [
            'like' => true,
            'dislike' => false
        ]);
    }
    
    /**
     * Create a dislike reaction.
     */
    public function dislike(): static
    {
        return $this->state(fn (array $attributes) => [
            'like' => false,
            'dislike' => true
        ]);
    }
}
