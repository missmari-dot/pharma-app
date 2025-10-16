<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pharmacie;
use App\Services\GeolocationService;
use Illuminate\Http\Request;

class PharmacieController extends Controller
{
    public function index()
    {
        return Pharmacie::with('pharmacien')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom_pharmacie' => 'required|string|max:255',
            'adresse_pharmacie' => 'required|string',
            'telephone_pharmacie' => 'required|string|max:20',
            'heure_ouverture' => 'required|date_format:H:i',
            'heure_fermeture' => 'required|date_format:H:i',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'pharmacien_id' => 'required|exists:users,id'
        ]);

        return Pharmacie::create($validated);
    }

    public function show(Pharmacie $pharmacie)
    {
        return $pharmacie->load(['pharmacien', 'produits']);
    }

    public function update(Request $request, Pharmacie $pharmacie)
    {
        $validated = $request->validate([
            'nom_pharmacie' => 'string|max:255',
            'adresse_pharmacie' => 'string',
            'telephone_pharmacie' => 'string|max:20',
            'heure_ouverture' => 'date_format:H:i',
            'heure_fermeture' => 'date_format:H:i',
            'est_de_garde' => 'boolean',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric'
        ]);

        $pharmacie->update($validated);
        return $pharmacie;
    }

    public function destroy(Pharmacie $pharmacie)
    {
        $pharmacie->delete();
        return response()->json(['message' => 'Pharmacie supprimée']);
    }

    public function pharmaciesProches(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'nullable|numeric|min:1|max:50',
            'use_google' => 'nullable|boolean'
        ]);

        $geolocationService = new GeolocationService();
        
        // Utiliser Google Maps si disponible et demandé
        if ($request->use_google && env('GOOGLE_MAPS_API_KEY')) {
            $pharmacies = $this->findWithGoogleMaps(
                $geolocationService,
                $request->latitude,
                $request->longitude,
                $request->radius ?? 5
            );
        } else {
            // Fallback vers calcul local
            $pharmacies = $geolocationService->findNearbyPharmacies(
                $request->latitude,
                $request->longitude,
                $request->radius ?? 5
            );
        }

        return response()->json($pharmacies);
    }

    private function findWithGoogleMaps($service, $userLat, $userLon, $radiusKm)
    {
        $pharmacies = Pharmacie::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        if ($pharmacies->isEmpty()) {
            return [];
        }

        $origins = [$userLat . ',' . $userLon];
        $destinations = $pharmacies->map(function($p) {
            return $p->latitude . ',' . $p->longitude;
        })->toArray();

        $distanceData = $service->getDistanceMatrix($origins, $destinations);
        
        if (!$distanceData) {
            return $service->findNearbyPharmacies($userLat, $userLon, $radiusKm);
        }

        $nearbyPharmacies = [];
        $elements = $distanceData['rows'][0]['elements'];

        foreach ($pharmacies as $index => $pharmacie) {
            $element = $elements[$index];
            
            if ($element['status'] === 'OK') {
                $distanceKm = $element['distance']['value'] / 1000;
                
                if ($distanceKm <= $radiusKm) {
                    $pharmacie->distance = round($distanceKm, 2);
                    $pharmacie->duration = $element['duration']['text'];
                    $nearbyPharmacies[] = $pharmacie;
                }
            }
        }

        usort($nearbyPharmacies, function($a, $b) {
            return $a->distance <=> $b->distance;
        });

        return $nearbyPharmacies;
    }

    public function geocodeAddress(Request $request)
    {
        $request->validate([
            'address' => 'required|string'
        ]);

        $geolocationService = new GeolocationService();
        $coordinates = $geolocationService->geocodeAddress($request->address);

        if ($coordinates) {
            return response()->json($coordinates);
        }

        return response()->json(['message' => 'Adresse non trouvée'], 404);
    }

    public function pharmaciesDeGarde(Request $request)
    {
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $produitId = $request->produit_id;
        
        $query = Pharmacie::where('est_de_garde', true);
        
        if ($produitId) {
            $query->whereHas('produits', function($q) use ($produitId) {
                $q->where('produit_id', $produitId)
                  ->where('quantite_disponible', '>', 0);
            });
        }
        
        if ($latitude && $longitude) {
            $query->selectRaw("*, 
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance", 
                [$latitude, $longitude, $latitude])
                ->orderBy('distance');
        }
        
        return $query->with(['pharmacien', 'produits'])->get();
    }
}