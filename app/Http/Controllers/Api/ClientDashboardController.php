<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Ordonnance;
use App\Models\Reservation;
use App\Models\Pharmacie;
use Illuminate\Http\Request;

class ClientDashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $client = $request->user();
        
        $dashboard = [
            'role' => 'client',
            'utilisateur' => [
                'nom' => $client->nom,
                'email' => $client->email,
                'telephone' => $client->telephone,
                'membre_depuis' => $client->created_at->diffForHumans()
            ],
            'resume_sante' => [
                'ordonnances_actives' => Ordonnance::where('client_id', $client->id)
                    ->where('statut', 'VALIDEE')
                    ->whereHas('reservation', function($q) {
                        $q->where('statut', 'ACTIVE');
                    })->count(),
                'reservations_en_attente' => Reservation::where('client_id', $client->id)
                    ->where('statut', 'ACTIVE')->count(),
                'prochains_renouvellements' => $this->getProchainRenouvellements($client)->count()
            ],
            'activites_recentes' => [
                'ordonnances_recentes' => Ordonnance::where('client_id', $client->id)
                    ->with('pharmacie:id,nom_pharmacie')
                    ->latest()->limit(3)->get(['id', 'pharmacie_id', 'statut', 'created_at']),
                'reservations_recentes' => Reservation::where('client_id', $client->id)
                    ->with('pharmacie:id,nom_pharmacie')
                    ->latest()->limit(3)->get(['id', 'pharmacie_id', 'statut', 'created_at'])
            ],
            'conseils_sante_personnalises' => $this->getConseilsSante(),
            'pharmacies_de_garde' => Pharmacie::where('est_de_garde', true)
                ->limit(2)->get(['nom_pharmacie', 'adresse_pharmacie', 'telephone_pharmacie']),
            'notifications_non_lues' => $this->getNotificationsNonLues($client)
        ];

        return response()->json($dashboard);
    }

    private function getSantePersonnelle($client)
    {
        return [
            'ordonnances_actives' => Ordonnance::where('client_id', $client->id)
                ->where('statut', 'VALIDEE')
                ->whereHas('reservation', function($q) {
                    $q->where('statut', 'ACTIVE');
                })->count(),
            'medicaments_reguliers' => $this->getMedicamentsReguliers($client),
            'prochains_renouvellements' => $this->getProchainRenouvellements($client),
            'historique_medical' => $this->getHistoriqueMedical($client)
        ];
    }

    private function getOrdonnancesData($client)
    {
        return [
            'total' => Ordonnance::where('client_id', $client->id)->count(),
            'en_attente' => Ordonnance::where('client_id', $client->id)->where('statut', 'ENVOYEE')->count(),
            'validees' => Ordonnance::where('client_id', $client->id)->where('statut', 'VALIDEE')->count(),
            'rejetees' => Ordonnance::where('client_id', $client->id)->where('statut', 'REJETEE')->count(),
            'ce_mois' => Ordonnance::where('client_id', $client->id)
                ->whereMonth('created_at', now()->month)->count()
        ];
    }

    private function getReservationsData($client)
    {
        return [
            'actives' => Reservation::where('client_id', $client->id)->where('statut', 'ACTIVE')->count(),
            'confirmees' => Reservation::where('client_id', $client->id)->where('statut', 'CONFIRMEE')->count(),
            'total' => Reservation::where('client_id', $client->id)->count(),
            'en_attente_retrait' => Reservation::where('client_id', $client->id)
                ->where('statut', 'ACTIVE')
                ->where('created_at', '<', now()->subHours(24))
                ->count()
        ];
    }

    private function getPharmaciesFavorites($client)
    {
        return Pharmacie::whereHas('ordonnances', function($q) use ($client) {
            $q->where('client_id', $client->id);
        })
        ->withCount(['ordonnances' => function($q) use ($client) {
            $q->where('client_id', $client->id);
        }])
        ->orderBy('ordonnances_count', 'desc')
        ->limit(3)
        ->get(['nom_pharmacie', 'adresse_pharmacie', 'telephone_pharmacie']);
    }

    private function getConseilsSante()
    {
        return \DB::table('conseil_santes')
            ->latest()
            ->limit(2)
            ->get(['titre', 'contenu', 'categorie', 'created_at']);
    }

    private function getActivitesRecentes($client)
    {
        $ordonnances = Ordonnance::where('client_id', $client->id)
            ->with('pharmacie:id,nom_pharmacie')
            ->latest()
            ->limit(5)
            ->get(['id', 'pharmacie_id', 'statut', 'created_at']);

        $reservations = Reservation::where('client_id', $client->id)
            ->with('pharmacie:id,nom_pharmacie')
            ->latest()
            ->limit(5)
            ->get(['id', 'pharmacie_id', 'statut', 'created_at']);

        return [
            'ordonnances_recentes' => $ordonnances,
            'reservations_recentes' => $reservations
        ];
    }

    private function getMedicamentsReguliers($client)
    {
        return \DB::table('ligne_reservations')
            ->join('reservations', 'ligne_reservations.reservation_id', '=', 'reservations.id')
            ->join('produits', 'ligne_reservations.produit_id', '=', 'produits.id')
            ->where('reservations.client_id', $client->id)
            ->where('reservations.statut', 'CONFIRMEE')
            ->select('produits.nom_produit', \DB::raw('COUNT(*) as frequence'))
            ->groupBy('produits.id', 'produits.nom_produit')
            ->orderBy('frequence', 'desc')
            ->limit(5)
            ->get();
    }

    private function getProchainRenouvellements($client)
    {
        return Reservation::where('client_id', $client->id)
            ->where('statut', 'CONFIRMEE')
            ->where('updated_at', '<=', now()->subMonth())
            ->with('lignesReservation.produit')
            ->limit(3)
            ->get();
    }

    private function getHistoriqueMedical($client)
    {
        return [
            'total_ordonnances' => Ordonnance::where('client_id', $client->id)->count(),
            'premiere_ordonnance' => Ordonnance::where('client_id', $client->id)->oldest()->first()?->created_at,
            'pharmacies_visitees' => Pharmacie::whereHas('ordonnances', function($q) use ($client) {
                $q->where('client_id', $client->id);
            })->count()
        ];
    }

    private function getNotificationsNonLues($client)
    {
        return \DB::table('notifications')
            ->where('user_id', $client->id)
            ->where('lu', false)
            ->count();
    }
}