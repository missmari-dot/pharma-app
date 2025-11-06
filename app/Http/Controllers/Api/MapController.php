<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pharmacie;
use App\Models\Produit;
use Illuminate\Http\Request;

class MapController extends Controller
{
    public function pharmaciesProches(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'rayon' => 'nullable|numeric|min:1|max:50',
            'de_garde' => 'nullable|boolean',
            'produit_id' => 'nullable|exists:produits,id'
        ]);

        $latitude = $validated['latitude'];
        $longitude = $validated['longitude'];
        $rayon = $validated['rayon'] ?? 10; // 10km par défaut

        $query = Pharmacie::select('*')
            ->selectRaw("
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
                cos(radians(longitude) - radians(?)) + sin(radians(?)) * 
                sin(radians(latitude)))) AS distance
            ", [$latitude, $longitude, $latitude])
            ->where('statut_validation', 'approved')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance', '<=', $rayon);

        // Filtre pharmacies de garde
        if (isset($validated['de_garde']) && $validated['de_garde']) {
            $query->where('est_de_garde', true);
        }

        // Filtre par produit disponible
        if (isset($validated['produit_id'])) {
            $query->whereHas('produits', function($q) use ($validated) {
                $q->where('produit_id', $validated['produit_id'])
                  ->where('pharmacie_produit.quantite_disponible', '>', 0);
            });
        }

        $pharmacies = $query->orderBy('distance')->get();

        return $pharmacies->map(function($pharmacie) {
            return [
                'id' => $pharmacie->id,
                'nom_pharmacie' => $pharmacie->nom_pharmacie,
                'adresse_pharmacie' => $pharmacie->adresse_pharmacie,
                'telephone_pharmacie' => $pharmacie->telephone_pharmacie,
                'latitude' => (float) $pharmacie->latitude,
                'longitude' => (float) $pharmacie->longitude,
                'est_de_garde' => (bool) $pharmacie->est_de_garde,
                'heure_ouverture' => $pharmacie->heure_ouverture,
                'heure_fermeture' => $pharmacie->heure_fermeture,
                'distance_km' => round($pharmacie->distance, 2),
                'statut' => $pharmacie->est_de_garde ? 'garde' : 'normale'
            ];
        });
    }

    public function itineraire(Request $request)
    {
        $validated = $request->validate([
            'depart_lat' => 'required|numeric|between:-90,90',
            'depart_lng' => 'required|numeric|between:-180,180',
            'arrivee_lat' => 'required|numeric|between:-90,90',
            'arrivee_lng' => 'required|numeric|between:-180,180'
        ]);

        // URL OpenRouteService (gratuit avec clé API)
        $url = "https://api.openrouteservice.org/v2/directions/driving-car";
        
        $params = [
            'start' => $validated['depart_lng'] . ',' . $validated['depart_lat'],
            'end' => $validated['arrivee_lng'] . ',' . $validated['arrivee_lat']
        ];

        $headers = [
            'Authorization: Bearer ' . env('OPENROUTE_API_KEY', '5b3ce3597851110001cf6248YOUR_API_KEY'),
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            // Fallback: retourner une ligne droite
            return response()->json([
                'type' => 'LineString',
                'coordinates' => [
                    [$validated['depart_lng'], $validated['depart_lat']],
                    [$validated['arrivee_lng'], $validated['arrivee_lat']]
                ],
                'distance' => $this->calculerDistance(
                    $validated['depart_lat'], $validated['depart_lng'],
                    $validated['arrivee_lat'], $validated['arrivee_lng']
                ),
                'duration' => null,
                'fallback' => true
            ]);
        }

        $data = json_decode($response, true);
        
        if (isset($data['features'][0]['geometry'])) {
            return response()->json([
                'type' => $data['features'][0]['geometry']['type'],
                'coordinates' => $data['features'][0]['geometry']['coordinates'],
                'distance' => $data['features'][0]['properties']['segments'][0]['distance'] ?? null,
                'duration' => $data['features'][0]['properties']['segments'][0]['duration'] ?? null,
                'fallback' => false
            ]);
        }

        return response()->json(['error' => 'Impossible de calculer l\'itinéraire'], 400);
    }

    private function calculerDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }
}