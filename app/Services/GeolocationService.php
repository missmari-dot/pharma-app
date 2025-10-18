<?php

namespace App\Services;

class GeolocationService
{
    public function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }

    public function findNearbyPharmacies($latitude, $longitude, $radius = 10)
    {
        // Get all pharmacies with coordinates and calculate distance in PHP for SQLite compatibility
        $pharmacies = \App\Models\Pharmacie::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();
            
        $nearbyPharmacies = [];
        
        foreach ($pharmacies as $pharmacie) {
            $distance = $this->calculateDistance(
                $latitude, 
                $longitude, 
                $pharmacie->latitude, 
                $pharmacie->longitude
            );
            
            if ($distance <= $radius) {
                $pharmacie->distance = round($distance, 2);
                $nearbyPharmacies[] = $pharmacie;
            }
        }
        
        // Sort by distance
        usort($nearbyPharmacies, function($a, $b) {
            return $a->distance <=> $b->distance;
        });
        
        return collect($nearbyPharmacies);
    }

    public function geocodeWithOpenStreetMap($address)
    {
        $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($address);
        
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if (!empty($data)) {
            return [
                'lat' => (float)$data[0]['lat'],
                'lng' => (float)$data[0]['lon']
            ];
        }
        
        return null;
    }
}