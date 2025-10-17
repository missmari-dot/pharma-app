<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PharmacieFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom_pharmacie' => fake()->company(),
            'adresse_pharmacie' => fake()->address(),
            'telephone_pharmacie' => fake()->phoneNumber(),
            'heure_ouverture' => '08:00',
            'heure_fermeture' => '18:00',
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'est_de_garde' => fake()->boolean(),
        ];
    }
}