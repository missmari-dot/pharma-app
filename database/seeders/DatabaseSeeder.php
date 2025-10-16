<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\Pharmacien;
use App\Models\Pharmacie;
use App\Models\Produit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Autorité de santé
        \App\Models\AutoriteSante::firstOrCreate(
            ['code_autorisation' => 'MSS-2024'],
            [
                'nom' => 'Ministère de la Santé du Sénégal',
                'type_controle' => 'MEDICAMENT'
            ]
        );

        // Pharmacien
        $pharmacienUser = User::firstOrCreate(
            ['email' => 'pharmacien@pharma.sn'],
            [
                'nom' => 'Dr. Amadou Diallo',
                'password' => Hash::make('password'),
                'telephone' => '221771234568',
                'adresse' => 'Plateau, Dakar',
                'date_naissance' => '1975-05-15',
                'role' => 'pharmacien'
            ]
        );

        $pharmacien = Pharmacien::firstOrCreate(['user_id' => $pharmacienUser->id]);

        // Pharmacie
        Pharmacie::firstOrCreate(
            ['nom_pharmacie' => 'Pharmacie du Plateau'],
            [
                'adresse_pharmacie' => 'Avenue Léopold Sédar Senghor, Dakar',
                'telephone_pharmacie' => '221338234567',
                'heure_ouverture' => '08:00',
                'heure_fermeture' => '20:00',
                'est_de_garde' => true,
                'latitude' => 14.6937,
                'longitude' => -17.4441,
                'pharmacien_id' => $pharmacien->id
            ]
        );

        // Client
        $clientUser = User::firstOrCreate(
            ['email' => 'client@pharma.sn'],
            [
                'nom' => 'Fatou Sall',
                'password' => Hash::make('password'),
                'telephone' => '221771234569',
                'adresse' => 'Médina, Dakar',
                'date_naissance' => '1990-03-20',
                'role' => 'client'
            ]
        );

        Client::firstOrCreate(['user_id' => $clientUser->id]);

        // Produits
        $produits = [
            [
                'nom_produit' => 'Paracétamol 500mg',
                'description' => 'Antalgique et antipyrétique',
                'prix' => 1500,
                'categorie' => 'MEDICAMENT',
                'necessite_ordonnance' => false
            ],
            [
                'nom_produit' => 'Amoxicilline 500mg',
                'description' => 'Antibiotique à large spectre',
                'prix' => 3500,
                'categorie' => 'MEDICAMENT',
                'necessite_ordonnance' => true
            ]
        ];

        foreach ($produits as $produitData) {
            Produit::firstOrCreate(
                ['nom_produit' => $produitData['nom_produit']],
                $produitData
            );
        }
    }
}