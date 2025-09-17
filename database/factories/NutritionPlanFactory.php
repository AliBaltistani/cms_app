<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\NutritionPlan;

/**
 * NutritionPlan Factory
 * 
 * Generates realistic test data for nutrition plans with proper relationships
 * and varied configurations for comprehensive testing scenarios
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NutritionPlan>
 * @package Database\Factories
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
class NutritionPlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = NutritionPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $goalTypes = ['weight_loss', 'weight_gain', 'maintenance', 'muscle_gain'];
        $statuses = ['active', 'inactive', 'draft'];
        $tags = [
            ['beginner', 'low-carb'],
            ['intermediate', 'high-protein'],
            ['advanced', 'keto'],
            ['vegetarian', 'gluten-free'],
            ['vegan', 'dairy-free'],
            ['mediterranean', 'heart-healthy'],
            ['paleo', 'whole-foods'],
            ['flexible', 'balanced']
        ];

        $planNames = [
            'Ultimate Weight Loss Plan',
            'Muscle Building Nutrition',
            'Balanced Lifestyle Diet',
            'Keto Transformation Program',
            'Mediterranean Wellness Plan',
            'High-Protein Athlete Diet',
            'Vegetarian Fitness Plan',
            'Beginner Healthy Eating',
            'Advanced Cutting Protocol',
            'Maintenance Nutrition Guide'
        ];

        return [
            'trainer_id' => $this->faker->boolean(70) ? User::where('role', 'trainer')->inRandomOrder()->first()?->id : null,
            'client_id' => $this->faker->boolean(60) ? User::where('role', 'client')->inRandomOrder()->first()?->id : null,
            'plan_name' => $this->faker->randomElement($planNames),
            'description' => $this->faker->paragraph(3),
            'media_url' => $this->faker->boolean(40) ? 'nutrition-plans/' . $this->faker->uuid() . '.jpg' : null,
            'status' => $this->faker->randomElement($statuses),
            'is_global' => $this->faker->boolean(20), // 20% chance of being global
            'tags' => $this->faker->randomElement($tags),
            'duration_days' => $this->faker->numberBetween(30, 365),
            'target_weight' => $this->faker->randomFloat(1, 50.0, 120.0),
            'goal_type' => $this->faker->randomElement($goalTypes),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    /**
     * Create a global nutrition plan (admin-created)
     *
     * @return static
     */
    public function global(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'trainer_id' => null,
                'client_id' => null,
                'is_global' => true,
                'status' => 'active',
            ];
        });
    }

    /**
     * Create an active nutrition plan
     *
     * @return static
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
            ];
        });
    }

    /**
     * Create a weight loss focused plan
     *
     * @return static
     */
    public function weightLoss(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'goal_type' => 'weight_loss',
                'plan_name' => 'Weight Loss Transformation Plan',
                'duration_days' => $this->faker->numberBetween(60, 180),
                'target_weight' => $this->faker->randomFloat(1, 55.0, 85.0),
                'tags' => ['weight-loss', 'low-carb', 'calorie-deficit'],
            ];
        });
    }

    /**
     * Create a muscle gain focused plan
     *
     * @return static
     */
    public function muscleGain(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'goal_type' => 'muscle_gain',
                'plan_name' => 'Muscle Building Nutrition Protocol',
                'duration_days' => $this->faker->numberBetween(90, 365),
                'target_weight' => $this->faker->randomFloat(1, 70.0, 110.0),
                'tags' => ['muscle-gain', 'high-protein', 'bulking'],
            ];
        });
    }

    /**
     * Create a plan with specific trainer and client
     *
     * @param int $trainerId
     * @param int $clientId
     * @return static
     */
    public function forTrainerAndClient(int $trainerId, int $clientId): static
    {
        return $this->state(function (array $attributes) use ($trainerId, $clientId) {
            return [
                'trainer_id' => $trainerId,
                'client_id' => $clientId,
                'is_global' => false,
                'status' => 'active',
            ];
        });
    }
}