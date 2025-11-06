<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;

class PharmacienReservationController extends Controller
{
    public function mesReservations(Request $request)
    {
        $pharmacien = $request->user()->pharmacien;
        $pharmacieIds = $pharmacien->pharmacies->pluck('id');

        $reservations = Reservation::with(['client', 'pharmacie', 'lignesReservation.produit'])
            ->whereIn('pharmacie_id', $pharmacieIds)
            ->when($request->statut, function($query, $statut) {
                return $query->where('statut', $statut);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'reservations' => $reservations->map(function($reservation) {
                return [
                    'id' => $reservation->id,
                    'code_retrait' => $reservation->code_retrait,
                    'client' => [
                        'nom' => $reservation->client->user->nom,
                        'telephone' => $reservation->client->user->telephone
                    ],
                    'date_reservation' => $reservation->date_reservation,
                    'statut' => $reservation->statut,
                    'montant_total' => $reservation->montant_total,
                    'produits' => $reservation->lignesReservation->map(function($ligne) {
                        return [
                            'nom_produit' => $ligne->produit->nom_produit,
                            'quantite_reservee' => $ligne->quantite_reservee,
                            'prix_unitaire' => $ligne->prix_unitaire
                        ];
                    })
                ];
            })
        ]);
    }

    public function historiqueTransactions(Request $request)
    {
        $pharmacien = $request->user()->pharmacien;
        $pharmacieIds = $pharmacien->pharmacies->pluck('id');

        $historique = Reservation::with(['client', 'pharmacie', 'lignesReservation.produit'])
            ->whereIn('pharmacie_id', $pharmacieIds)
            ->whereIn('statut', ['retiree', 'validee'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'historique' => $historique->map(function($reservation) {
                return [
                    'id' => $reservation->id,
                    'code_retrait' => $reservation->code_retrait,
                    'client' => [
                        'nom' => $reservation->client->user->nom,
                        'telephone' => $reservation->client->user->telephone
                    ],
                    'date_reservation' => $reservation->date_reservation,
                    'date_retrait' => $reservation->updated_at,
                    'statut' => $reservation->statut,
                    'montant_total' => $reservation->montant_total,
                    'produits' => $reservation->lignesReservation->map(function($ligne) {
                        return [
                            'nom_produit' => $ligne->produit->nom_produit,
                            'quantite_reservee' => $ligne->quantite_reservee,
                            'prix_unitaire' => $ligne->prix_unitaire
                        ];
                    })
                ];
            })
        ]);
    }
}