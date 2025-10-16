<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use App\Models\Pharmacie;
use Illuminate\Http\Request;

class RechercheGeographiqueController extends Controller
{
    public function rechercherMedicaments(Request $request)
    {
        $request->validate([
            'nom_produit' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'rayon' => 'nullable|numeric|min:1|max:50'
        ]);

        $rayon = $request->rayon ?? 10;
        $nomProduit = $request->nom_produit;
        
        $pharmacies = Pharmacie::selectRaw("pharmacies.*, 
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance", 
            [$request->latitude, $request->longitude, $request->latitude])
            ->whereHas('produits', function($query) use ($nomProduit) {
                $query->where('nom_produit', 'LIKE', "%{$nomProduit}%")
                      ->where('quantite_disponible', '>', 0);
            })
            ->with(['produits' => function($query) use ($nomProduit) {
                $query->where('nom_produit', 'LIKE', "%{$nomProduit}%")
                      ->where('quantite_disponible', '>', 0)
                      ->withPivot('quantite_disponible');
            }])
            ->having('distance', '<', $rayon)
            ->orderBy('distance')
            ->get();

        return response()->json([
            'produit_recherche' => $nomProduit,
            'rayon_km' => $rayon,
            'pharmacies_trouvees' => $pharmacies->count(),
            'pharmacies' => $pharmacies->map(function($pharmacie) {
                return [
                    'id' => $pharmacie->id,
                    'nom_pharmacie' => $pharmacie->nom_pharmacie,
                    'adresse_pharmacie' => $pharmacie->adresse_pharmacie,
                    'telephone_pharmacie' => $pharmacie->telephone_pharmacie,
                    'distance_km' => round($pharmacie->distance, 2),
                    'est_de_garde' => $pharmacie->est_de_garde,
                    'produits_disponibles' => $pharmacie->produits->map(function($produit) {
                        return [
                            'nom_produit' => $produit->nom_produit,
                            'prix' => $produit->prix,
                            'quantite_disponible' => $produit->pivot->quantite_disponible
                        ];
                    })
                ];
            })
        ]);
    }

    public function rechercherDansZone(Request $request)
    {
        $request->validate([
            'zone' => 'required|string',
            'nom_produit' => 'required|string',
            'rayon' => 'nullable|numeric|min:1|max:50'
        ]);

        // Géocodage de la zone (ex: "Dakar Almadies")
        $coordinates = $this->geocodeZone($request->zone);
        
        if (!$coordinates) {
            return response()->json(['message' => 'Zone non trouvée'], 404);
        }

        $request->merge([
            'latitude' => $coordinates['lat'],
            'longitude' => $coordinates['lng']
        ]);

        return $this->rechercherMedicaments($request);
    }

    private function geocodeZone($zone)
    {
        // Zones prédéfinies pour le Sénégal
        $zones = [
            'dakar almadies' => ['lat' => 14.7392, 'lng' => -17.5069],
            'dakar plateau' => ['lat' => 14.6928, 'lng' => -17.4467],
            'pikine' => ['lat' => 14.7549, 'lng' => -17.3985],
            'guediawaye' => ['lat' => 14.7692, 'lng' => -17.4056],
            'thiaroye' => ['lat' => 14.7833, 'lng' => -17.3833]
        ];

        $zoneKey = strtolower($zone);
        return $zones[$zoneKey] ?? null;
    }
}