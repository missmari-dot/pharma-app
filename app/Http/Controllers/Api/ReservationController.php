<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Ordonnance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $query = Reservation::with(['client', 'pharmacie', 'ordonnance', 'lignesReservation.produit']);

        if ($request->user()->role === 'client') {
            $query->where('client_id', $request->user()->client->id);
        }

        // Filtrer par statut si fourni
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        // Trier par date de création (plus récent en premier)
        $query->orderBy('created_at', 'desc');

        return $query->get()->map(function($reservation) {
            return [
                'id' => $reservation->id,
                'date_reservation' => $reservation->date_reservation,
                'statut' => $reservation->statut,
                'montant_total' => $reservation->montant_total,
                'pharmacie' => [
                    'id' => $reservation->pharmacie->id,
                    'nom_pharmacie' => $reservation->pharmacie->nom_pharmacie,
                    'adresse_pharmacie' => $reservation->pharmacie->adresse_pharmacie,
                    'telephone_pharmacie' => $reservation->pharmacie->telephone_pharmacie
                ],
                'produits' => $reservation->lignesReservation->map(function($ligne) {
                    return [
                        'nom_produit' => $ligne->produit->nom_produit,
                        'quantite_reservee' => $ligne->quantite_reservee,
                        'prix_unitaire' => $ligne->prix_unitaire,
                        'sous_total' => $ligne->getSousTotal()
                    ];
                }),
                'ordonnance_id' => $reservation->ordonnance_id
            ];
        });
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pharmacie_id' => 'required|exists:pharmacies,id',
            'ordonnance_id' => 'nullable|exists:ordonnances,id',
            'ordonnance_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'lignes_reservation' => 'required|array|min:1',
            'lignes_reservation.*.produit_id' => 'required|exists:produits,id',
            'lignes_reservation.*.quantite' => 'required|integer|min:1'
        ]);

        // Vérifier l'ordonnance si fournie
        $ordonnance = null;
        if ($validated['ordonnance_id']) {
            $ordonnance = Ordonnance::find($validated['ordonnance_id']);
            if ($ordonnance->statut !== 'VALIDEE') {
                return response()->json(['error' => 'Ordonnance non validée'], 400);
            }
            if ($ordonnance->client_id !== $request->user()->client->id) {
                return response()->json(['error' => 'Accès refusé'], 403);
            }
        }
        
        // Créer une ordonnance si image fournie
        if ($request->hasFile('ordonnance_image')) {
            $imagePath = $request->file('ordonnance_image')->store('ordonnances', 'public');
            
            $ordonnance = Ordonnance::create([
                'client_id' => $request->user()->client->id,
                'pharmacie_id' => $validated['pharmacie_id'],
                'image_ordonnance' => $imagePath,
                'statut' => 'EN_ATTENTE',
                'date_prescription' => now()
            ]);
        }

        // Vérifier les produits et ordonnances requises
        $pharmacie = \App\Models\Pharmacie::find($validated['pharmacie_id']);
        foreach ($validated['lignes_reservation'] as $ligne) {
            $produit = \App\Models\Produit::find($ligne['produit_id']);
            
            // Vérifier si ordonnance requise
            if ($produit->necessite_ordonnance && !$ordonnance) {
                return response()->json([
                    'error' => "Le produit {$produit->nom_produit} nécessite une ordonnance",
                    'requires_prescription' => true
                ], 400);
            }
            
            // Vérifier le stock
            $stockPharmacie = $pharmacie->produits()->where('produit_id', $ligne['produit_id'])->first();
            if (!$stockPharmacie || $stockPharmacie->pivot->quantite_disponible < $ligne['quantite']) {
                return response()->json([
                    'error' => "Stock insuffisant pour {$produit->nom_produit}"
                ], 400);
            }
        }

        // Créer la réservation
        $reservation = Reservation::create([
            'client_id' => $request->user()->client->id,
            'pharmacie_id' => $validated['pharmacie_id'],
            'ordonnance_id' => $ordonnance ? $ordonnance->id : null,
            'date_reservation' => now(),
            'statut' => $ordonnance && $ordonnance->statut === 'EN_ATTENTE' ? 'en_attente_validation' : 'en_attente',
            'montant_total' => 0
        ]);

        // Créer les lignes de réservation
        $montantTotal = 0;
        foreach ($validated['lignes_reservation'] as $ligne) {
            $produit = \App\Models\Produit::find($ligne['produit_id']);
            $ligneReservation = $reservation->lignesReservation()->create([
                'produit_id' => $ligne['produit_id'],
                'quantite_reservee' => $ligne['quantite'],
                'prix_unitaire' => $produit->prix
            ]);

            $montantTotal += $ligneReservation->getSousTotal();

            // Réserver le stock
            $pharmacie->produits()->updateExistingPivot($ligne['produit_id'], [
                'quantite_disponible' => DB::raw("quantite_disponible - {$ligne['quantite']}")
            ]);
        }

        // Mettre à jour le montant total
        $reservation->update(['montant_total' => $montantTotal]);

        // Notifier le pharmacien
        $notificationService = new \App\Services\NotificationService();
        $notificationService->notifierReservationPrete($reservation);

        return response()->json([
            'message' => 'Réservation créée avec succès',
            'reservation' => $reservation->load(['ordonnance', 'pharmacie', 'lignesReservation.produit'])
        ], 201);
    }

    public function show(Reservation $reservation)
    {
        return $reservation->load(['client', 'pharmacie', 'ordonnance', 'lignesReservation.produit']);
    }

    public function confirmerRetrait(Request $request, Reservation $reservation)
    {
        if ($request->user()->role !== 'pharmacien') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        // Vérifier que le pharmacien a accès à cette pharmacie
        $pharmacien = $request->user()->pharmacien;
        if (!$pharmacien || !$pharmacien->pharmacies->contains($reservation->pharmacie_id)) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $reservation->update(['statut' => 'confirmee']);

        // Libérer le stock réservé
        foreach ($reservation->lignesReservation as $ligne) {
            $reservation->pharmacie->produits()->updateExistingPivot($ligne->produit_id, [
                'quantite_disponible' => DB::raw("quantite_disponible + {$ligne->quantite_reservee}")
            ]);
        }

        return response()->json([
            'message' => 'Retrait confirmé avec succès',
            'reservation' => $reservation->load(['client', 'pharmacie', 'lignesReservation.produit'])
        ]);
    }

    public function validerAchat(Request $request, Reservation $reservation)
    {
        if ($request->user()->role !== 'pharmacien') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        // Vérifier que le pharmacien a accès à cette pharmacie
        $pharmacien = $request->user()->pharmacien;
        if (!$pharmacien || !$pharmacien->pharmacies->contains($reservation->pharmacie_id)) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        if ($reservation->statut !== 'confirmee') {
            return response()->json(['error' => 'La réservation doit être confirmée avant validation'], 400);
        }

        $reservation->update(['statut' => 'validee']);

        return response()->json([
            'message' => 'Achat validé avec succès',
            'reservation' => $reservation->load(['client', 'pharmacie', 'lignesReservation.produit'])
        ]);
    }

    public function annuler(Request $request, Reservation $reservation)
    {
        if ($reservation->client_id !== $request->user()->client->id) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        if ($reservation->statut === 'validee') {
            return response()->json(['error' => 'Impossible d\'annuler une réservation validée'], 400);
        }

        $reservation->update(['statut' => 'annulee']);

        // Libérer le stock réservé
        foreach ($reservation->lignesReservation as $ligne) {
            $reservation->pharmacie->produits()->updateExistingPivot($ligne->produit_id, [
                'quantite_disponible' => DB::raw("quantite_disponible + {$ligne->quantite_reservee}")
            ]);
        }

        return response()->json([
            'message' => 'Réservation annulée avec succès',
            'reservation' => $reservation->load(['client', 'pharmacie', 'lignesReservation.produit'])
        ]);
    }

    public function annulerParPharmacien(Request $request, Reservation $reservation)
    {
        if ($request->user()->role !== 'pharmacien') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $pharmacien = $request->user()->pharmacien;
        if (!$pharmacien || !$pharmacien->pharmacies->contains($reservation->pharmacie_id)) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        if ($reservation->statut === 'validee') {
            return response()->json(['error' => 'Impossible d\'annuler une réservation validée'], 400);
        }

        $reservation->update(['statut' => 'annulee']);

        // Libérer le stock réservé
        foreach ($reservation->lignesReservation as $ligne) {
            $reservation->pharmacie->produits()->updateExistingPivot($ligne->produit_id, [
                'quantite_disponible' => DB::raw("quantite_disponible + {$ligne->quantite_reservee}")
            ]);
        }

        return response()->json([
            'message' => 'Réservation annulée avec succès',
            'reservation' => $reservation->load(['client', 'pharmacie', 'lignesReservation.produit'])
        ]);
    }
}
