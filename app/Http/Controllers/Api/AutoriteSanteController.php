<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ordonnance;
use App\Models\Pharmacie;
use App\Models\Medicament;
use Illuminate\Http\Request;

class AutoriteSanteController extends Controller
{
    public function rapportDispensation(Request $request)
    {
        $query = Ordonnance::with(['client', 'pharmacie', 'reservation'])
            ->where('statut', 'VALIDEE');

        if ($request->has('date_debut')) {
            $query->where('created_at', '>=', $request->date_debut);
        }

        if ($request->has('date_fin')) {
            $query->where('created_at', '<=', $request->date_fin);
        }

        return $query->paginate(50);
    }

    public function auditPharmacies()
    {
        return Pharmacie::with(['pharmacien.user', 'ordonnances', 'reservations'])
            ->withCount(['ordonnances', 'reservations'])
            ->get();
    }

    public function statistiquesConsommation(Request $request)
    {
        $stats = [
            'ordonnances_total' => Ordonnance::count(),
            'ordonnances_validees' => Ordonnance::where('statut', 'VALIDEE')->count(),
            'ordonnances_rejetees' => Ordonnance::where('statut', 'REJETEE')->count(),
            'pharmacies_actives' => Pharmacie::whereHas('ordonnances')->count(),
            'medicaments_prescrits' => Medicament::whereHas('produit.lignesReservation')->count()
        ];

        return response()->json($stats);
    }

    public function prescriptionsSuspectes()
    {
        return Ordonnance::where('statut', 'REJETEE')
            ->orWhereHas('reservation.lignesReservation', function($query) {
                $query->where('quantite_reservee', '>', 10); // Quantité suspecte
            })
            ->with(['client', 'pharmacie'])
            ->get();
    }

    public function controleConformite(Request $request)
    {
        $validated = $request->validate([
            'pharmacie_id' => 'required|exists:pharmacies,id',
            'type_controle' => 'required|in:ROUTINE,ALERTE,PLAINTE'
        ]);

        // Log du contrôle
        \Log::info('Contrôle autorité de santé', [
            'pharmacie_id' => $validated['pharmacie_id'],
            'type' => $validated['type_controle'],
            'controleur' => auth()->user()->nom
        ]);

        return response()->json(['message' => 'Contrôle enregistré']);
    }
}