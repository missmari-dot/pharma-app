<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutoriteSante extends User
{
    protected $table = 'users';
    
    protected static function booted()
    {
        static::addGlobalScope('autorite_sante', function ($builder) {
            $builder->where('role', 'autorite_sante');
        });
    }

    protected $fillable = [
        'nom',
        'email', 
        'password',
        'telephone',
        'adresse',
        'date_naissance',
        'role',
        'code_autorisation',
        'type_controle',
        'organisme'
    ];

    protected $attributes = [
        'role' => 'autorite_sante'
    ];

    public function getDashboardData()
    {
        return [
            'surveillance_reglementaire' => $this->getSurveillanceData(),
            'statistiques_sante_publique' => $this->getStatistiquesSantePublique(),
            'controles_recents' => $this->getControlesRecents(),
            'alertes_pharmacovigilance' => $this->getAlertesPharmaco(),
            'rapports_disponibles' => $this->getRapportsDisponibles()
        ];
    }

    private function getSurveillanceData()
    {
        return [
            'ordonnances_controlees' => Ordonnance::whereIn('statut', ['VALIDEE', 'REJETEE'])->count(),
            'prescriptions_suspectes' => $this->getPrescriptionsSuspectes()->count(),
            'pharmacies_surveillees' => Pharmacie::count(),
            'controles_effectues_mois' => $this->getControlesEffectues()
        ];
    }

    private function getStatistiquesSantePublique()
    {
        return [
            'medicaments_dispenses' => Reservation::where('statut', 'CONFIRMEE')->count(),
            'ordonnances_ce_mois' => Ordonnance::whereMonth('created_at', now()->month)->count(),
            'pharmacies_conformes' => $this->getPharmaciesConformes(),
            'taux_conformite' => $this->calculerTauxConformite()
        ];
    }

    public function verifierCadreLegal($medicament)
    {
        if ($medicament->necessite_ordonnance && !$medicament->ordonnance) {
            return false;
        }
        
        return $this->verifierPosologie($medicament);
    }

    private function verifierPosologie($medicament)
    {
        return !empty($medicament->posologie) && !empty($medicament->description);
    }

    public function getPrescriptionsSuspectes()
    {
        return Ordonnance::where('statut', 'REJETEE')
            ->orWhereHas('reservation.lignesReservation', function($query) {
                $query->where('quantite_reservee', '>', 10);
            });
    }

    private function getControlesEffectues()
    {
        return \DB::table('controles_autorite')
            ->where('autorite_id', $this->id)
            ->whereMonth('created_at', now()->month)
            ->count() ?? 0;
    }

    private function getPharmaciesConformes()
    {
        return Pharmacie::whereDoesntHave('ordonnances', function($query) {
            $query->where('statut', 'REJETEE')
                ->where('created_at', '>=', now()->subMonth());
        })->count();
    }

    private function calculerTauxConformite()
    {
        $total = Pharmacie::count();
        $conformes = $this->getPharmaciesConformes();
        return $total > 0 ? round(($conformes / $total) * 100, 2) : 0;
    }

    private function getControlesRecents()
    {
        return \DB::table('controles_autorite')
            ->where('autorite_id', $this->id)
            ->latest()
            ->limit(5)
            ->get() ?? collect();
    }

    private function getAlertesPharmaco()
    {
        return [
            'medicaments_retires' => 0,
            'effets_indesirables' => 0,
            'interactions_detectees' => 0
        ];
    }

    private function getRapportsDisponibles()
    {
        return [
            'dispensation_mensuelle' => true,
            'audit_pharmacies' => true,
            'consommation_medicaments' => true,
            'prescriptions_analysees' => true,
            'pharmacovigilance' => true
        ];
    }

    public function controlePharmacies()
    {
        return $this->hasMany(Pharmacie::class, 'autorite_id');
    }
}