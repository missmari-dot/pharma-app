<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OrdonnanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => \App\Models\Client::factory(),
            'pharmacie_id' => \App\Models\Pharmacie::factory(),
            'photo_url' => 'ordonnances/test.jpg',
            'statut' => 'en_attente',
            'date_envoi' => fake()->date(),
        ];
    }
}