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
        return \App\Models\Pharmacie::selectRaw("*, 
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance", 
            [$latitude, $longitude, $latitude])
            ->having('distance', '<', $radius)
            ->orderBy('distance')
            ->get();
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