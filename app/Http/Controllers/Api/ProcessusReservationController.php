<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ordonnance;
use App\Models\Reservation;
use Illuminate\Http\Request;

class ProcessusReservationController extends Controller
{
    public function envoyerOrdonnance(Request $request)
    {
        $validated = $request->validate([
            'pharmacie_id' => 'required|exists:pharmacies,id',
            'photo_ordonnance' => 'required|image|max:2048'
        ]);

        $photoPath = $request->file('photo_ordonnance')->store('ordonnances');

        return Ordonnance::create([
            'client_id' => auth()->id(),
            'pharmacie_id' => $validated['pharmacie_id'],
            'photo_url' => $photoPath,
            'statut' => 'ENVOYEE',
            'date_envoi' => now()
        ]);
    }

    public function validerOrdonnance(Request $request, Ordonnance $ordonnance)
    {
        $ordonnance->update(['statut' => 'VALIDEE']);
        return response()->json(['message' => 'Ordonnance validée']);
    }

    public function creerReservation(Ordonnance $ordonnance)
    {
        if ($ordonnance->statut !== 'VALIDEE') {
            return response()->json(['error' => 'Ordonnance non validée'], 400);
        }

        $reservation = $ordonnance->genererReservation();
        return $reservation->load('ordonnance');
    }

    public function confirmerRetrait(Reservation $reservation)
    {
        $reservation->update(['statut' => 'CONFIRMEE']);
        return response()->json(['message' => 'Produits récupérés']);
    }
}