<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use Illuminate\Http\Request;

class ProduitController extends Controller
{
    public function index(Request $request)
    {
        $pharmacieId = $request->pharmacie_id;
        
        $query = Produit::with(['medicament', 'produitParapharmacie']);
        
        if ($request->has('search')) {
            $query->where('nom_produit', 'like', '%' . $request->search . '%');
        }
        
        if ($request->has('categorie')) {
            $query->where('categorie', $request->categorie);
        }

        if ($pharmacieId) {
            $query->with(['pharmacies' => function($q) use ($pharmacieId) {
                $q->where('pharmacie_id', $pharmacieId)
                  ->withPivot('quantite_disponible');
            }]);
        } else {
            $query->with(['pharmacies' => function($q) {
                $q->withPivot('quantite_disponible');
            }]);
        }

        $produits = $query->paginate(20);
        
        $produits->getCollection()->transform(function($produit) use ($pharmacieId) {
            if ($pharmacieId) {
                $stockPharmacie = $produit->pharmacies->first();
                $produit->stock_pharmacie = $stockPharmacie ? $stockPharmacie->pivot->quantite_disponible : 0;
            } else {
                $produit->stock_total = $produit->pharmacies->sum('pivot.quantite_disponible');
                $produit->pharmacies_count = $produit->pharmacies->where('pivot.quantite_disponible', '>', 0)->count();
            }
            unset($produit->pharmacies);
            return $produit;
        });

        return $produits;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom_produit' => 'required|string|max:255',
            'description' => 'required|string',
            'prix' => 'required|numeric|min:0',
            'image' => 'nullable|string',
            'categorie' => 'required|in:MEDICAMENT,PARAPHARMACIE',
            'stock' => 'required|integer|min:0',
            'necessite_ordonnance' => 'boolean'
        ]);

        return Produit::create($validated);
    }

    public function show(Produit $produit, Request $request)
    {
        $pharmacieId = $request->pharmacie_id;
        
        if ($pharmacieId) {
            $produit->load(['pharmacies' => function($query) use ($pharmacieId) {
                $query->where('pharmacie_id', $pharmacieId)
                      ->withPivot('quantite_disponible');
            }, 'medicament', 'produitParapharmacie']);
            
            $stockPharmacie = $produit->pharmacies->first();
            $produit->stock_pharmacie = $stockPharmacie ? $stockPharmacie->pivot->quantite_disponible : 0;
        } else {
            $produit->load(['pharmacies' => function($query) {
                $query->withPivot('quantite_disponible');
            }, 'medicament', 'produitParapharmacie']);
            
            $produit->stock_total = $produit->pharmacies->sum('pivot.quantite_disponible');
            $produit->pharmacies_disponibles = $produit->pharmacies->where('pivot.quantite_disponible', '>', 0)->map(function($pharmacie) {
                return [
                    'id' => $pharmacie->id,
                    'nom_pharmacie' => $pharmacie->nom_pharmacie,
                    'stock' => $pharmacie->pivot->quantite_disponible
                ];
            });
        }
        
        unset($produit->pharmacies);
        return $produit;
    }

    public function update(Request $request, Produit $produit)
    {
        $validated = $request->validate([
            'nom_produit' => 'string|max:255',
            'description' => 'string',
            'prix' => 'numeric|min:0',
            'image' => 'nullable|string',
            'stock' => 'integer|min:0'
        ]);

        $produit->update($validated);
        return $produit;
    }

    public function rechercher(Request $request)
    {
        $request->validate([
            'terme' => 'required|string|min:2'
        ]);

        return Produit::where('nom_produit', 'like', '%' . $request->terme . '%')
            ->orWhere('description', 'like', '%' . $request->terme . '%')
            ->with(['medicament', 'produitParapharmacie'])
            ->limit(10)
            ->get();
    }

    public function pharmaciesDisponibles(Produit $produit, Request $request)
    {
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        
        $query = $produit->pharmacies()
            ->wherePivot('quantite_disponible', '>', 0)
            ->withPivot('quantite_disponible');
            
        if ($latitude && $longitude) {
            $query->selectRaw("pharmacies.*, 
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance", 
                [$latitude, $longitude, $latitude])
                ->orderBy('distance');
        }
        
        return $query->get()->map(function($pharmacie) {
            return [
                'id' => $pharmacie->id,
                'nom_pharmacie' => $pharmacie->nom_pharmacie,
                'adresse_pharmacie' => $pharmacie->adresse_pharmacie,
                'telephone_pharmacie' => $pharmacie->telephone_pharmacie,
                'latitude' => $pharmacie->latitude,
                'longitude' => $pharmacie->longitude,
                'est_de_garde' => $pharmacie->est_de_garde,
                'stock_disponible' => $pharmacie->pivot->quantite_disponible,
                'distance_km' => isset($pharmacie->distance) ? round($pharmacie->distance, 2) : null
            ];
        });
    }
}