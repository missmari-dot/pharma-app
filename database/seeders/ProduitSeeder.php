<?php

namespace Database\Seeders;

use App\Models\Produit;
use App\Models\Medicament;
use App\Models\ProduitParapharmacie;
use App\Models\Pharmacie;
use Illuminate\Database\Seeder;

class ProduitSeeder extends Seeder
{
    public function run(): void
    {
        $medicaments = [
            [
                'nom_produit' => 'Paracétamol 500mg',
                'description' => 'Antalgique et antipyrétique',
                'prix' => 1500,
                'necessite_ordonnance' => false,
                'posologie' => '1 comprimé 3 fois par jour'
            ],
            [
                'nom_produit' => 'Amoxicilline 500mg',
                'description' => 'Antibiotique à large spectre',
                'prix' => 3500,
                'necessite_ordonnance' => true,
                'posologie' => '1 gélule 2 fois par jour pendant 7 jours'
            ]
        ];
        
        $parapharmacies = [
            [
                'nom_produit' => 'Vitamine C 1000mg',
                'description' => 'Complément alimentaire',
                'prix' => 2500,
                'marque' => 'Bayer',
                'categorie_parapharmacie' => 'Vitamines'
            ],
            [
                'nom_produit' => 'Crème hydratante',
                'description' => 'Soin pour peau sèche',
                'prix' => 4500,
                'marque' => 'Nivea',
                'categorie_parapharmacie' => 'Cosmétiques'
            ]
        ];

        $pharmacie = Pharmacie::first();

        // Créer les médicaments
        foreach ($medicaments as $medData) {
            $produit = Produit::create([
                'nom_produit' => $medData['nom_produit'],
                'description' => $medData['description'],
                'prix' => $medData['prix'],
                'categorie' => 'Médicament',
                'necessite_ordonnance' => $medData['necessite_ordonnance']
            ]);
            
            Medicament::create([
                'produit_id' => $produit->id,
                'posologie' => $medData['posologie']
            ]);
            
            $pharmacie->produits()->attach($produit->id, [
                'quantite_disponible' => rand(10, 100)
            ]);
        }
        
        // Créer les produits de parapharmacie
        foreach ($parapharmacies as $paraData) {
            $produit = Produit::create([
                'nom_produit' => $paraData['nom_produit'],
                'description' => $paraData['description'],
                'prix' => $paraData['prix'],
                'categorie' => 'Parapharmacie',
                'necessite_ordonnance' => false
            ]);
            
            ProduitParapharmacie::create([
                'produit_id' => $produit->id,
                'marque' => $paraData['marque'],
                'categorie_parapharmacie' => $paraData['categorie_parapharmacie']
            ]);
            
            $pharmacie->produits()->attach($produit->id, [
                'quantite_disponible' => rand(10, 100)
            ]);
        }
    }
}