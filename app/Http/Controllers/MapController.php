<?php

namespace App\Http\Controllers;

use App\Models\Pharmacie;
use Illuminate\Http\Request;

class MapController extends Controller
{
    public function index()
    {
        return view('map.openstreetmap');
    }

    public function pharmaciesAvecProduit(Request $request)
    {
        $produit = $request->produit;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        
        $pharmacies = Pharmacie::when($produit, function($query) use ($produit) {
            $query->whereHas('produits', function($q) use ($produit) {
                $q->where('nom_produit', 'LIKE', "%{$produit}%")
                  ->where('quantite_disponible', '>', 0);
            });
        })
        ->when($latitude && $longitude, function($query) use ($latitude, $longitude) {
            $query->selectRaw("*, 
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance", 
                [$latitude, $longitude, $latitude])
                ->orderBy('distance');
        })
        ->get();

        return response()->json($pharmacies);
    }
}