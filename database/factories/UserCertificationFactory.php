<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserCertification>
 */
class UserCertificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $certifications = [
            'Certified Personal Trainer (CPT)',
            'Strength and Conditioning Specialist (CSCS)',
            'Yoga Teacher Training (200-hour)',
            'Pilates Instructor Certification',
            'Nutrition Specialist Certification',
            'CrossFit Level 1 Trainer',
            'Functional Movement Screen (FMS)',
            'TRX Suspension Training Certification',
            'Kettlebell Instructor Certification',
            'Group Fitness Instructor',
            'Sports Massage Therapy',
            'Corrective Exercise Specialist',
            'Senior Fitness Specialist',
            'Youth Exercise Specialist',
            'Aquatic Fitness Instructor'
        ];
        
        return [
            'user_id' => User::factory(),
            'certificate_name' => $this->faker->randomElement($certifications),
            'doc' => $this->faker->optional(0.7)->randomElement([
                'certifications/cert_' . $this->faker->uuid . '.pdf',
                'certifications/cert_' . $this->faker->uuid . '.jpg',
                'certifications/cert_' . $this->faker->uuid . '.png'
            ])
        ];
    }
}
