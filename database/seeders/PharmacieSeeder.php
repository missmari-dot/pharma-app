<?php

namespace Database\Seeders;

use App\Models\Pharmacie;
use App\Models\Pharmacien;
use Illuminate\Database\Seeder;

class PharmacieSeeder extends Seeder
{
    public function run(): void
    {
        $pharmacien = Pharmacien::first();

        $pharmacie = Pharmacie::create([
            'nom_pharmacie' => 'Pharmacie Lotty',
            'adresse_pharmacie' => 'Avenue Cheikh Anta Diop, Dakar, Sénégal',
            'telephone_pharmacie' => '+221338234567',
            'heure_ouverture' => '08:00',
            'heure_fermeture' => '20:00',
            'est_de_garde' => true,
            'latitude' => 14.6937,
            'longitude' => -17.4441,
            'pharmacien_id' => $pharmacien->id
        ]);
        
        // Synchroniser les pharmacies associées
        $pharmacien->syncPharmaciesAssociees();
    }
}