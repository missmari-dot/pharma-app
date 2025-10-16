<?php

namespace App\Services;

use App\Models\AutoriteSante;
use App\Models\Medicament;
use App\Models\Ordonnance;

class ValidationReglementaireService
{
    public function validerVenteMedicament(Medicament $medicament, ?Ordonnance $ordonnance = null)
    {
        // Vérification prescription obligatoire
        if ($medicament->necessite_ordonnance && !$ordonnance) {
            throw new \Exception('Ordonnance requise pour ce médicament');
        }

        // Validation par l'autorité de santé
        if (!$medicament->verifierPrescription()) {
            throw new \Exception('Médicament non conforme aux réglementations');
        }

        return true;
    }

    public function controleStock(Medicament $medicament, int $quantite)
    {
        if ($quantite <= 0) {
            throw new \Exception('Quantité invalide');
        }

        return $medicament->autorite?->verifierCadreLegal($medicament) ?? true;
    }

    public function validerOrdonnance(Ordonnance $ordonnance)
    {
        $erreurs = [];
        $avertissements = [];

        // Vérifier que l'ordonnance a une photo
        if (empty($ordonnance->photo_url)) {
            $erreurs[] = 'Photo d\'ordonnance manquante';
        }

        // Vérifier la date d'envoi (pas plus de 7 jours)
        if ($ordonnance->date_envoi->diffInDays(now()) > 7) {
            $erreurs[] = 'Ordonnance expirée (plus de 7 jours)';
        }

        // Vérifier que la pharmacie est autorisée
        $autorite = AutoriteSante::first();
        if ($autorite && !$autorite->verifierCadreLegal($ordonnance)) {
            $erreurs[] = 'Ordonnance non conforme au cadre légal';
        }

        return [
            'valide' => empty($erreurs),
            'message' => empty($erreurs) ? 'Ordonnance valide' : 'Ordonnance non valide',
            'details' => [
                'erreurs' => $erreurs,
                'avertissements' => $avertissements
            ]
        ];
    }

    public function verifierInteractions(array $medicamentIds)
    {
        $interactions = [];

        // Récupérer les médicaments
        $medicaments = Medicament::whereIn('id', $medicamentIds)->get();

        // Vérifier les interactions connues (simplifié)
        foreach ($medicaments as $medicament) {
            foreach ($medicaments as $autreMedicament) {
                if ($medicament->id !== $autreMedicament->id) {
                    $interaction = $this->verifierInteractionMedicamenteuse($medicament, $autreMedicament);
                    if ($interaction) {
                        $interactions[] = $interaction;
                    }
                }
            }
        }

        return $interactions;
    }

    public function verifierPosologie(Medicament $medicament, string $posologieDemandee, ?int $agePatient = null, ?float $poidsPatient = null)
    {
        $recommandations = [];
        $valide = true;

        // Vérifier la posologie standard
        if (empty($medicament->posologie)) {
            $recommandations[] = 'Posologie standard non définie pour ce médicament';
        }

        // Vérifier l'âge du patient
        if ($agePatient !== null) {
            if ($agePatient < 12 && str_contains($medicament->posologie, 'adulte')) {
                $recommandations[] = 'Attention: posologie adulte pour un patient mineur';
                $valide = false;
            }
        }

        // Vérifier le poids du patient
        if ($poidsPatient !== null && $poidsPatient < 50) {
            $recommandations[] = 'Attention: patient de faible poids, vérifier la posologie';
        }

        return [
            'valide' => $valide,
            'message' => $valide ? 'Posologie acceptable' : 'Posologie à vérifier',
            'recommandations' => $recommandations
        ];
    }

    private function verifierInteractionMedicamenteuse(Medicament $medicament1, Medicament $medicament2)
    {
        // Interactions connues (simplifié - en réalité, utiliser une base de données d'interactions)
        $interactionsConnues = [
            'Paracétamol' => ['Warfarine'],
            'Aspirine' => ['Warfarine', 'Méthotrexate']
        ];

        $nom1 = $medicament1->produit->nom_produit ?? '';
        $nom2 = $medicament2->produit->nom_produit ?? '';

        if (isset($interactionsConnues[$nom1]) && in_array($nom2, $interactionsConnues[$nom1])) {
            return [
                'medicament1' => $nom1,
                'medicament2' => $nom2,
                'niveau' => 'Modéré',
                'description' => 'Interaction médicamenteuse détectée. Consulter un professionnel de santé.'
            ];
        }

        return null;
    }

    public function verifierConformitePharmacie($pharmacie)
    {
        $conformite = [
            'autorisation_exercice' => true,
            'stock_securise' => true,
            'personnel_qualifie' => true
        ];

        // Vérifier l'autorisation d'exercice
        if (empty($pharmacie->pharmacien->numero_ordre)) {
            $conformite['autorisation_exercice'] = false;
        }

        // Vérifier le stock sécurisé
        $produitsSansStock = $pharmacie->produits()->wherePivot('quantite_disponible', 0)->count();
        if ($produitsSansStock > 10) {
            $conformite['stock_securise'] = false;
        }

        return $conformite;
    }
}
