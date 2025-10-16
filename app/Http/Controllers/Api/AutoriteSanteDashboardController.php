<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AutoriteSante;
use App\Models\Ordonnance;
use App\Models\Pharmacie;
use App\Models\Reservation;
use Illuminate\Http\Request;

class AutoriteSanteDashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $autorite = AutoriteSante::find($request->user()->id);

        $dashboard = [
            'role' => 'autorite_sante',
            'utilisateur' => [
                'nom' => $autorite->nom,
                'email' => $autorite->email,
                'organisme' => $autorite->organisme ?? 'Autorité de Santé du Sénégal',
                'code_autorisation' => $autorite->code_autorisation
            ],
            'surveillance_reglementaire' => $this->getSurveillanceData(),
            'statistiques_sante_publique' => $this->getStatistiquesSantePublique(),
            'controles_qualite' => $this->getControlesQualite(),
            'alertes_prioritaires' => $this->getAlertesPrioritaires(),
            'rapports_disponibles' => $this->getRapportsDisponibles(),
            'activites_recentes' => $this->getActivitesRecentes($autorite),
            'notifications_non_lues' => $this->getNotificationsNonLues($autorite)
        ];

        return response()->json($dashboard);
    }

    private function getSurveillanceData()
    {
        return [
            'ordonnances_controlees' => Ordonnance::whereIn('statut', ['VALIDEE', 'REJETEE'])->count(),
            'prescriptions_suspectes' => Ordonnance::where('statut', 'REJETEE')->count(),
            'pharmacies_surveillees' => Pharmacie::count(),
            'controles_effectues_mois' => $this->getControlesEffectuesMois(),
            'taux_conformite_global' => $this->calculerTauxConformiteGlobal()
        ];
    }

    private function getStatistiquesSantePublique()
    {
        return [
            'medicaments_dispenses_mois' => Reservation::where('statut', 'CONFIRMEE')
                ->whereMonth('updated_at', now()->month)
                ->count(),
            'ordonnances_ce_mois' => Ordonnance::whereMonth('created_at', now()->month)->count(),
            'pharmacies_actives' => Pharmacie::whereHas('ordonnances', function($q) {
                $q->whereMonth('created_at', now()->month);
            })->count(),
            'evolution_mensuelle' => $this->getEvolutionMensuelle()
        ];
    }

    private function getControlesQualite()
    {
        return [
            'pharmacies_auditees' => $this->getPharmaciesAuditees(),
            'non_conformites_detectees' => $this->getNonConformites(),
            'actions_correctives' => $this->getActionsCorrectives(),
            'prochains_controles' => $this->getProchainControles()
        ];
    }

    private function getAlertesPrioritaires()
    {
        return [
            'prescriptions_anormales' => $this->getPrescriptionsAnormales(),
            'stocks_critiques' => $this->getStocksCritiques(),
            'pharmacies_non_conformes' => $this->getPharmaciesNonConformes(),
            'medicaments_expires' => $this->getMedicamentsExpires()
        ];
    }

    private function getRapportsDisponibles()
    {
        return [
            'dispensation_mensuelle' => [
                'disponible' => true,
                'derniere_maj' => now()->format('Y-m-d H:i'),
                'url' => '/api/autorite/rapport-dispensation'
            ],
            'audit_pharmacies' => [
                'disponible' => true,
                'derniere_maj' => now()->format('Y-m-d H:i'),
                'url' => '/api/autorite/audit-pharmacies'
            ],
            'consommation_medicaments' => [
                'disponible' => true,
                'derniere_maj' => now()->format('Y-m-d H:i'),
                'url' => '/api/autorite/statistiques-consommation'
            ],
            'pharmacovigilance' => [
                'disponible' => true,
                'derniere_maj' => now()->format('Y-m-d H:i'),
                'url' => '/api/autorite/pharmacovigilance'
            ]
        ];
    }

    private function getActivitesRecentes($autorite)
    {
        return [
            'controles_recents' => $this->getControlesRecents($autorite),
            'rapports_generes' => $this->getRapportsGeneres($autorite),
            'alertes_traitees' => $this->getAlertesTraitees($autorite)
        ];
    }

    private function getControlesEffectuesMois()
    {
        return \DB::table('controles_autorite')
            ->whereMonth('created_at', now()->month)
            ->count() ?? 0;
    }

    private function calculerTauxConformiteGlobal()
    {
        $total = Pharmacie::count();
        $conformes = Pharmacie::whereDoesntHave('ordonnances', function($query) {
            $query->where('statut', 'REJETEE')
                ->where('created_at', '>=', now()->subMonth());
        })->count();

        return $total > 0 ? round(($conformes / $total) * 100, 2) : 100;
    }

    private function getEvolutionMensuelle()
    {
        $moisActuel = Ordonnance::whereMonth('created_at', now()->month)->count();
        $moisPrecedent = Ordonnance::whereMonth('created_at', now()->subMonth()->month)->count();

        $evolution = $moisPrecedent > 0 ?
            round((($moisActuel - $moisPrecedent) / $moisPrecedent) * 100, 2) : 0;

        return [
            'mois_actuel' => $moisActuel,
            'mois_precedent' => $moisPrecedent,
            'pourcentage_evolution' => $evolution
        ];
    }

    private function getPharmaciesAuditees()
    {
        return \DB::table('controles_autorite')
            ->whereMonth('created_at', now()->month)
            ->distinct('pharmacie_id')
            ->count() ?? 0;
    }

    private function getNonConformites()
    {
        return \DB::table('controles_autorite')
            ->where('resultat', 'NON_CONFORME')
            ->whereMonth('created_at', now()->month)
            ->count() ?? 0;
    }

    private function getActionsCorrectives()
    {
        // TODO: Implement actions_correctives table
        return 0;
    }

    private function getProchainControles()
    {
        // TODO: Implement controles_planifies table
        return 0;
    }

    private function getPrescriptionsAnormales()
    {
        return Ordonnance::where('statut', 'REJETEE')
            ->whereDate('created_at', today())
            ->count();
    }

    private function getStocksCritiques()
    {
        return \DB::table('pharmacie_produit')
            ->where('quantite_disponible', '<', 5)
            ->count();
    }

    private function getPharmaciesNonConformes()
    {
        return Pharmacie::whereHas('ordonnances', function($query) {
            $query->where('statut', 'REJETEE')
                ->where('created_at', '>=', now()->subWeek());
        })->count();
    }

    private function getMedicamentsExpires()
    {
        return \DB::table('pharmacie_produit')
            ->where('date_expiration', '<', now())
            ->count() ?? 0;
    }

    private function getControlesRecents($autorite)
    {
        return \DB::table('controles_autorite')
            ->where('autorite_id', $autorite->id)
            ->latest()
            ->limit(5)
            ->get();
    }

    private function getRapportsGeneres($autorite)
    {
        // TODO: Implement rapports_generes table
        return collect();
    }

    private function getAlertesTraitees($autorite)
    {
        // TODO: Implement alertes_traitees table
        return collect();
    }

    private function getNotificationsNonLues($autorite)
    {
        return \DB::table('notifications')
            ->where('user_id', $autorite->id)
            ->where('lu', false)
            ->count();
    }
}
