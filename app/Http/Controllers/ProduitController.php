<?php

namespace App\Http\Controllers;

use App\Models\Produit;
use App\Services\UploadService;
use Illuminate\Http\Request;

class ProduitController extends Controller
{
    protected $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }
    public function index(Request $request)
    {
        $pharmacieId = $request->pharmacie_id;
        
        if ($pharmacieId) {
            return Produit::with(['pharmacies' => function($query) use ($pharmacieId) {
                $query->where('pharmacie_id', $pharmacieId)
                      ->withPivot('quantite_disponible');
            }])->get()->map(function($produit) {
                $stockPharmacie = $produit->pharmacies->first();
                $produit->stock_pharmacie = $stockPharmacie ? $stockPharmacie->pivot->quantite_disponible : 0;
                unset($produit->pharmacies);
                return $produit;
            });
        }
        
        return Produit::with(['pharmacies' => function($query) {
            $query->withPivot('quantite_disponible');
        }])->get()->map(function($produit) {
            $produit->stock_total = $produit->pharmacies->sum('pivot.quantite_disponible');
            $produit->pharmacies_count = $produit->pharmacies->count();
            unset($produit->pharmacies);
            return $produit;
        });
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom_produit' => 'required|string',
            'prix' => 'required|numeric',
            'categorie' => 'required|string',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        $data = $request->all();
        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadService->uploadProduitImage($request->file('image'));
        }

        return Produit::create($data);
    }

    public function show(Produit $produit, Request $request)
    {
        $pharmacieId = $request->pharmacie_id;
        
        if ($pharmacieId) {
            $produit->load(['pharmacies' => function($query) use ($pharmacieId) {
                $query->where('pharmacie_id', $pharmacieId)
                      ->withPivot('quantite_disponible');
            }]);
            
            $stockPharmacie = $produit->pharmacies->first();
            $produit->stock_pharmacie = $stockPharmacie ? $stockPharmacie->pivot->quantite_disponible : 0;
        } else {
            $produit->load(['pharmacies' => function($query) {
                $query->withPivot('quantite_disponible');
            }]);
            
            $produit->stock_total = $produit->pharmacies->sum('pivot.quantite_disponible');
            $produit->pharmacies_disponibles = $produit->pharmacies->map(function($pharmacie) {
                return [
                    'id' => $pharmacie->id,
                    'nom_pharmacie' => $pharmacie->nom_pharmacie,
                    'stock' => $pharmacie->pivot->quantite_disponible
                ];
            });
        }
        
        return $produit;
    }

    public function update(Request $request, Produit $produit)
    {
        $request->validate([
            'stock' => 'integer|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        $data = $request->all();
        if ($request->hasFile('image')) {
            if ($produit->image) {
                $this->uploadService->deleteFile($produit->image);
            }
            $data['image'] = $this->uploadService->uploadProduitImage($request->file('image'));
        }

        $produit->update($data);
        return $produit;
    }

    public function destroy(Produit $produit)
    {
        $produit->delete();
        return response()->noContent();
    }
}
