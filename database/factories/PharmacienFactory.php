<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PharmacienFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'numero_ordre' => fake()->unique()->numerify('######'),
            'specialite' => fake()->randomElement(['Pharmacie clinique', 'Pharmacie hospitalière', 'Pharmacie industrielle']),
        ];
    }
}