<?php

namespace Database\Seeders;

use App\Models\Ordonnance;
use App\Models\Client;
use App\Models\Pharmacie;
use Illuminate\Database\Seeder;

class OrdonnanceSeeder extends Seeder
{
    public function run(): void
    {
        $client = Client::first();
        $pharmacie = Pharmacie::first();

        Ordonnance::create([
            'client_id' => $client->id,
            'pharmacie_id' => $pharmacie->id,
            'photo_url' => 'ordonnances/exemple.jpg',
            'statut' => 'en_attente',
            'date_envoi' => now()->toDateString()
        ]);
    }
}