<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Ordonnance;
use App\Models\Reservation;
use App\Models\Pharmacie;
use Illuminate\Http\Request;

class PharmacienDashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $pharmacien = $request->user();
        $pharmacie = $pharmacien->pharmacien?->pharmacies()->first();

        $dashboard = [
            'role' => 'pharmacien',
            'utilisateur' => [
                'nom' => $pharmacien->nom,
                'email' => $pharmacien->email,
                'pharmacie' => $pharmacie?->nom_pharmacie ?? 'Aucune pharmacie associée',
                'licence' => $pharmacien->pharmacien?->numero_licence ?? 'Non défini'
            ],
            'activite_quotidienne' => $this->getActiviteQuotidienne($pharmacie),
            'gestion_ordonnances' => $this->getGestionOrdonnances($pharmacie),
            'gestion_stocks' => $this->getGestionStocks($pharmacie),
            'performance_pharmacie' => $this->getPerformancePharmacien($pharmacie),
            'clients_reguliers' => $this->getClientsReguliers($pharmacie),
            'conseils_publies' => $this->getConseilsPublies($pharmacien),
            'activites_recentes' => $this->getActivitesRecentes($pharmacie),
            'notifications_non_lues' => $this->getNotificationsNonLues($pharmacien)
        ];

        return response()->json($dashboard);
    }

    private function getActiviteQuotidienne($pharmacie)
    {
        if (!$pharmacie) return [];

        return [
            'ordonnances_recues_aujourd_hui' => Ordonnance::where('pharmacie_id', $pharmacie->id)
                ->whereDate('created_at', today())
                ->count(),
            'ordonnances_traitees_aujourd_hui' => Ordonnance::where('pharmacie_id', $pharmacie->id)
                ->whereDate('updated_at', today())
                ->whereIn('statut', ['VALIDEE', 'REJETEE'])
                ->count(),
            'reservations_retirees_aujourd_hui' => Reservation::where('pharmacie_id', $pharmacie->id)
                ->where('statut', 'CONFIRMEE')
                ->whereDate('updated_at', today())
                ->count(),
            'chiffre_affaires_estime' => $this->getChiffreAffairesJour($pharmacie)
        ];
    }

    private function getGestionOrdonnances($pharmacie)
    {
        if (!$pharmacie) return [];

        return [
            'en_attente_validation' => Ordonnance::where('pharmacie_id', $pharmacie->id)
                ->where('statut', 'ENVOYEE')
                ->count(),
            'validees_ce_mois' => Ordonnance::where('pharmacie_id', $pharmacie->id)
                ->where('statut', 'VALIDEE')
                ->whereMonth('updated_at', now()->month)
                ->count(),
            'rejetees_ce_mois' => Ordonnance::where('pharmacie_id', $pharmacie->id)
                ->where('statut', 'REJETEE')
                ->whereMonth('updated_at', now()->month)
                ->count(),
            'temps_traitement_moyen' => $this->getTempsTraitementMoyen($pharmacie)
        ];
    }

    private function getGestionStocks($pharmacie)
    {
        if (!$pharmacie) return [];

        return [
            'produits_stock_faible' => $this->getProduitsStockFaible($pharmacie),
            'produits_expires' => $this->getProduitsExpires($pharmacie),
            'valeur_stock_total' => $this->getValeurStockTotal($pharmacie),
            'rotation_stock' => $this->getRotationStock($pharmacie)
        ];
    }

    private function getPerformancePharmacien($pharmacie)
    {
        if (!$pharmacie) return [];

        return [
            'taux_validation' => $this->getTauxValidation($pharmacie),
            'satisfaction_client' => $this->getSatisfactionClient($pharmacie),
            'revenus_mensuels' => $this->getRevenusMensuels($pharmacie),
            'evolution_activite' => $this->getEvolutionActivite($pharmacie)
        ];
    }

    private function getClientsReguliers($pharmacie)
    {
        if (!$pharmacie) return [];

        return \DB::table('ordonnances')
            ->join('users', 'ordonnances.client_id', '=', 'users.id')
            ->where('ordonnances.pharmacie_id', $pharmacie->id)
            ->where('ordonnances.statut', 'VALIDEE')
            ->select('users.nom', 'users.email', \DB::raw('COUNT(*) as nb_ordonnances'))
            ->groupBy('users.id', 'users.nom', 'users.email')
            ->orderBy('nb_ordonnances', 'desc')
            ->limit(5)
            ->get();
    }

    private function getConseilsPublies($pharmacien)
    {
        return \DB::table('conseil_santes')
            ->where('pharmacien_id', $pharmacien->id)
            ->latest()
            ->limit(3)
            ->get(['titre', 'categorie', 'created_at']);
    }

    private function getActivitesRecentes($pharmacie)
    {
        if (!$pharmacie) return [];

        return [
            'ordonnances_recentes' => Ordonnance::where('pharmacie_id', $pharmacie->id)
                ->with('client:id,nom')
                ->latest()
                ->limit(10)
                ->get(['id', 'client_id', 'statut', 'created_at']),
            'reservations_recentes' => Reservation::where('pharmacie_id', $pharmacie->id)
                ->with('client:id,nom')
                ->latest()
                ->limit(10)
                ->get(['id', 'client_id', 'statut', 'created_at'])
        ];
    }

    private function getChiffreAffairesJour($pharmacie)
    {
        return \DB::table('ligne_reservations')
            ->join('reservations', 'ligne_reservations.reservation_id', '=', 'reservations.id')
            ->join('produits', 'ligne_reservations.produit_id', '=', 'produits.id')
            ->where('reservations.pharmacie_id', $pharmacie->id)
            ->where('reservations.statut', 'CONFIRMEE')
            ->whereDate('reservations.updated_at', today())
            ->sum(\DB::raw('ligne_reservations.quantite_reservee * produits.prix')) ?? 0;
    }

    private function getTempsTraitementMoyen($pharmacie)
    {
        $ordonnances = Ordonnance::where('pharmacie_id', $pharmacie->id)
            ->whereIn('statut', ['VALIDEE', 'REJETEE'])
            ->whereMonth('updated_at', now()->month)
            ->get(['created_at', 'updated_at']);

        if ($ordonnances->isEmpty()) return 0;

        $totalMinutes = $ordonnances->sum(function($ordonnance) {
            return $ordonnance->created_at->diffInMinutes($ordonnance->updated_at);
        });

        return round($totalMinutes / $ordonnances->count(), 2);
    }

    private function getProduitsStockFaible($pharmacie)
    {
        return $pharmacie->produits()
            ->wherePivot('quantite_disponible', '<', 10)
            ->count();
    }

    private function getProduitsExpires($pharmacie)
    {
        return \DB::table('pharmacie_produit')
            ->where('pharmacie_id', $pharmacie->id)
            ->where('date_expiration', '<', now())
            ->count() ?? 0;
    }

    private function getValeurStockTotal($pharmacie)
    {
        return \DB::table('pharmacie_produit')
            ->join('produits', 'pharmacie_produit.produit_id', '=', 'produits.id')
            ->where('pharmacie_produit.pharmacie_id', $pharmacie->id)
            ->sum(\DB::raw('pharmacie_produit.quantite_disponible * produits.prix')) ?? 0;
    }

    private function getRotationStock($pharmacie)
    {
        $ventesMonth = \DB::table('ligne_reservations')
            ->join('reservations', 'ligne_reservations.reservation_id', '=', 'reservations.id')
            ->where('reservations.pharmacie_id', $pharmacie->id)
            ->where('reservations.statut', 'CONFIRMEE')
            ->whereMonth('reservations.updated_at', now()->month)
            ->sum('ligne_reservations.quantite_reservee') ?? 0;

        $stockMoyen = \DB::table('pharmacie_produit')
            ->where('pharmacie_id', $pharmacie->id)
            ->avg('quantite_disponible') ?? 1;

        return $stockMoyen > 0 ? round($ventesMonth / $stockMoyen, 2) : 0;
    }

    private function getTauxValidation($pharmacie)
    {
        $total = Ordonnance::where('pharmacie_id', $pharmacie->id)
            ->whereIn('statut', ['VALIDEE', 'REJETEE'])
            ->count();

        $validees = Ordonnance::where('pharmacie_id', $pharmacie->id)
            ->where('statut', 'VALIDEE')
            ->count();

        return $total > 0 ? round(($validees / $total) * 100, 2) : 0;
    }

    private function getSatisfactionClient($pharmacie)
    {
        // Simulation - à implémenter avec système d'évaluation
        return rand(85, 98);
    }

    private function getRevenusMensuels($pharmacie)
    {
        return \DB::table('ligne_reservations')
            ->join('reservations', 'ligne_reservations.reservation_id', '=', 'reservations.id')
            ->join('produits', 'ligne_reservations.produit_id', '=', 'produits.id')
            ->where('reservations.pharmacie_id', $pharmacie->id)
            ->where('reservations.statut', 'CONFIRMEE')
            ->whereMonth('reservations.updated_at', now()->month)
            ->sum(\DB::raw('ligne_reservations.quantite_reservee * produits.prix')) ?? 0;
    }

    private function getEvolutionActivite($pharmacie)
    {
        $moisActuel = Ordonnance::where('pharmacie_id', $pharmacie->id)
            ->whereMonth('created_at', now()->month)
            ->count();

        $moisPrecedent = Ordonnance::where('pharmacie_id', $pharmacie->id)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->count();

        $evolution = $moisPrecedent > 0 ?
            round((($moisActuel - $moisPrecedent) / $moisPrecedent) * 100, 2) : 0;

        return [
            'mois_actuel' => $moisActuel,
            'mois_precedent' => $moisPrecedent,
            'pourcentage_evolution' => $evolution
        ];
    }

    private function getNotificationsNonLues($pharmacien)
    {
        return \DB::table('notifications')
            ->where('user_id', $pharmacien->id)
            ->where('lu', false)
            ->count();
    }
}
