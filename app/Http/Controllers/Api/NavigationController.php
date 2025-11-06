<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pharmacie;
use Illuminate\Http\Request;

class NavigationController extends Controller
{
    public function pharmacieLaPlusProche(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180'
        ]);

        $userLat = $validated['latitude'];
        $userLng = $validated['longitude'];

        // Trouver la pharmacie la plus proche avec calcul de distance
        $pharmacieLaPlusProche = Pharmacie::select('*')
            ->selectRaw("
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + 
                    sin(radians(?)) * sin(radians(latitude))
                )) AS distance_km
            ", [$userLat, $userLng, $userLat])
            ->where('statut_validation', 'approved')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('distance_km')
            ->first();

        if (!$pharmacieLaPlusProche) {
            return response()->json(['message' => 'Aucune pharmacie trouvée'], 404);
        }

        // Calculer le temps estimé (vitesse moyenne 30 km/h en ville)
        $tempsEstimeMinutes = round(($pharmacieLaPlusProche->distance_km / 30) * 60);

        return response()->json([
            'pharmacie' => [
                'id' => $pharmacieLaPlusProche->id,
                'nom_pharmacie' => $pharmacieLaPlusProche->nom_pharmacie,
                'adresse_pharmacie' => $pharmacieLaPlusProche->adresse_pharmacie,
                'latitude' => $pharmacieLaPlusProche->latitude,
                'longitude' => $pharmacieLaPlusProche->longitude,
                'telephone_pharmacie' => $pharmacieLaPlusProche->telephone_pharmacie,
                'est_de_garde' => $pharmacieLaPlusProche->est_de_garde,
                'heure_ouverture' => $pharmacieLaPlusProche->heure_ouverture,
                'heure_fermeture' => $pharmacieLaPlusProche->heure_fermeture
            ],
            'navigation' => [
                'distance_km' => round($pharmacieLaPlusProche->distance_km, 2),
                'distance_m' => round($pharmacieLaPlusProche->distance_km * 1000),
                'temps_estime_minutes' => $tempsEstimeMinutes,
                'position_client' => [
                    'latitude' => $userLat,
                    'longitude' => $userLng
                ],
                'google_maps_url' => "https://www.google.com/maps/dir/{$userLat},{$userLng}/{$pharmacieLaPlusProche->latitude},{$pharmacieLaPlusProche->longitude}",
                'directions_api_url' => "https://api.openrouteservice.org/v2/directions/driving-car?api_key=YOUR_KEY&start={$userLng},{$userLat}&end={$pharmacieLaPlusProche->longitude},{$pharmacieLaPlusProche->latitude}"
            ]
        ]);
    }

    public function itineraire(Request $request)
    {
        $validated = $request->validate([
            'depart_lat' => 'required|numeric',
            'depart_lng' => 'required|numeric',
            'arrivee_lat' => 'required|numeric',
            'arrivee_lng' => 'required|numeric'
        ]);

        // URL pour OpenRouteService (gratuit)
        $directionsUrl = "https://api.openrouteservice.org/v2/directions/driving-car?" . http_build_query([
            'api_key' => env('OPENROUTE_API_KEY', 'YOUR_API_KEY'),
            'start' => $validated['depart_lng'] . ',' . $validated['depart_lat'],
            'end' => $validated['arrivee_lng'] . ',' . $validated['arrivee_lat']
        ]);

        return response()->json([
            'google_maps_url' => "https://www.google.com/maps/dir/{$validated['depart_lat']},{$validated['depart_lng']}/{$validated['arrivee_lat']},{$validated['arrivee_lng']}",
            'openroute_url' => $directionsUrl,
            'coordinates' => [
                'depart' => [$validated['depart_lat'], $validated['depart_lng']],
                'arrivee' => [$validated['arrivee_lat'], $validated['arrivee_lng']]
            ]
        ]);
    }
}