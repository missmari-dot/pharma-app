<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;

class NotificationPersonnaliseeService
{
    public function notifierNouveauProduitDisponible($userId, $produit, $pharmacie)
    {
        Notification::create([
            'user_id' => $userId,
            'titre' => 'Nouveau produit disponible',
            'message' => "Le produit {$produit->nom_produit} est maintenant disponible à {$pharmacie->nom_pharmacie}",
            'type' => 'produit_disponible',
            'data' => json_encode([
                'produit_id' => $produit->id,
                'pharmacie_id' => $pharmacie->id
            ])
        ]);
    }

    public function notifierPromotionPersonnalisee($userId, $message, $produits = [])
    {
        Notification::create([
            'user_id' => $userId,
            'titre' => 'Offre spéciale pour vous',
            'message' => $message,
            'type' => 'promotion_personnalisee',
            'data' => json_encode(['produits' => $produits])
        ]);
    }

    public function notifierRappelMedicament($userId, $medicament, $heureRappel)
    {
        Notification::create([
            'user_id' => $userId,
            'titre' => 'Rappel médicament',
            'message' => "N'oubliez pas de prendre votre {$medicament} à {$heureRappel}",
            'type' => 'rappel_medicament',
            'data' => json_encode(['medicament' => $medicament, 'heure' => $heureRappel])
        ]);
    }

    public function notifierPharmacieProcheFermee($userId, $pharmacie, $pharmaciesAlternatives)
    {
        Notification::create([
            'user_id' => $userId,
            'titre' => 'Pharmacie fermée',
            'message' => "{$pharmacie->nom_pharmacie} est fermée. Voici des alternatives proches.",
            'type' => 'pharmacie_fermee',
            'data' => json_encode(['alternatives' => $pharmaciesAlternatives])
        ]);
    }

    public function notifierConseilSantePersonnalise($userId, $conseil)
    {
        Notification::create([
            'user_id' => $userId,
            'titre' => 'Conseil santé personnalisé',
            'message' => $conseil,
            'type' => 'conseil_sante',
            'data' => json_encode([])
        ]);
    }
}