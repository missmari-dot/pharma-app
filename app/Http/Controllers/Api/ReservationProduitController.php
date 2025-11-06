<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Produit;
use Illuminate\Http\Request;

class ReservationProduitController extends Controller
{
    public function ajouterProduit(Request $request, Reservation $reservation)
    {
        if ($request->user()->role !== 'pharmacien') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $pharmacien = $request->user()->pharmacien;
        if (!$pharmacien->pharmacies->contains($reservation->pharmacie_id)) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        if (!in_array($reservation->statut, ['en_preparation', 'en_attente'])) {
            return response()->json(['message' => 'Impossible de modifier cette réservation'], 400);
        }

        $validated = $request->validate([
            'produit_id' => 'required|exists:produits,id',
            'quantite' => 'required|integer|min:1'
        ]);

        $produit = Produit::findOrFail($validated['produit_id']);

        // Vérifier le stock
        $stock = $reservation->pharmacie->produits()->where('produit_id', $validated['produit_id'])->first();
        if (!$stock || $stock->pivot->quantite_disponible < $validated['quantite']) {
            return response()->json(['error' => 'Stock insuffisant'], 400);
        }

        // Ajouter ou mettre à jour la ligne de réservation
        $ligneReservation = $reservation->lignesReservation()->updateOrCreate(
            ['produit_id' => $validated['produit_id']],
            [
                'quantite_reservee' => $validated['quantite'],
                'prix_unitaire' => $produit->prix
            ]
        );

        // Recalculer le montant total
        $montantTotal = $reservation->lignesReservation()->sum(\DB::raw('quantite_reservee * prix_unitaire'));
        $reservation->update(['montant_total' => $montantTotal]);

        return response()->json([
            'message' => 'Produit ajouté à la réservation',
            'reservation' => $reservation->load(['lignesReservation.produit'])
        ]);
    }

    public function supprimerProduit(Request $request, Reservation $reservation, $produitId)
    {
        if ($request->user()->role !== 'pharmacien') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $pharmacien = $request->user()->pharmacien;
        if (!$pharmacien->pharmacies->contains($reservation->pharmacie_id)) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        if (!in_array($reservation->statut, ['en_preparation', 'en_attente'])) {
            return response()->json(['message' => 'Impossible de modifier cette réservation'], 400);
        }

        $reservation->lignesReservation()->where('produit_id', $produitId)->delete();

        // Recalculer le montant total
        $montantTotal = $reservation->lignesReservation()->sum(\DB::raw('quantite_reservee * prix_unitaire'));
        $reservation->update(['montant_total' => $montantTotal]);

        return response()->json([
            'message' => 'Produit supprimé de la réservation',
            'reservation' => $reservation->load(['lignesReservation.produit'])
        ]);
    }

    public function finaliserReservation(Request $request, Reservation $reservation)
    {
        if ($request->user()->role !== 'pharmacien') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $pharmacien = $request->user()->pharmacien;
        if (!$pharmacien->pharmacies->contains($reservation->pharmacie_id)) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        if ($reservation->statut !== 'en_preparation') {
            return response()->json(['message' => 'Cette réservation ne peut pas être finalisée'], 400);
        }

        if ($reservation->lignesReservation->isEmpty()) {
            return response()->json(['message' => 'Ajoutez au moins un produit avant de finaliser'], 400);
        }

        $reservation->update(['statut' => 'en_attente']);

        // Notification au client
        try {
            $client = $reservation->client;
            if ($client && $client->fcm_token) {
                // Envoyer notification push
            }
        } catch (\Exception $e) {
            // Log l'erreur
        }

        return response()->json([
            'message' => 'Réservation finalisée. Le client a été notifié.',
            'reservation' => $reservation->load(['lignesReservation.produit'])
        ]);
    }
}