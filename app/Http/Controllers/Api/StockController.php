<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pharmacie;
use App\Models\Produit;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Pharmacie $pharmacie)
    {
        return $pharmacie->produits()
            ->withPivot('quantite_disponible')
            ->get()
            ->map(function($produit) {
                return [
                    'id' => $produit->id,
                    'nom_produit' => $produit->nom_produit,
                    'prix' => $produit->prix,
                    'stock_pharmacie' => $produit->pivot->quantite_disponible
                ];
            });
    }

    public function update(Request $request, Pharmacie $pharmacie, Produit $produit)
    {
        $request->validate([
            'quantite' => 'required|integer|min:0'
        ]);

        $pharmacie->produits()->updateExistingPivot($produit->id, [
            'quantite_disponible' => $request->quantite
        ]);

        return response()->json(['message' => 'Stock mis à jour']);
    }

    public function incrementer(Request $request, Pharmacie $pharmacie, Produit $produit)
    {
        $request->validate([
            'quantite' => 'required|integer|min:1'
        ]);

        $stock = $pharmacie->produits()->where('produit_id', $produit->id)->first();
        $nouvelleQuantite = ($stock->pivot->quantite_disponible ?? 0) + $request->quantite;

        $pharmacie->produits()->syncWithoutDetaching([
            $produit->id => ['quantite_disponible' => $nouvelleQuantite]
        ]);

        return response()->json(['message' => 'Stock incrémenté', 'nouveau_stock' => $nouvelleQuantite]);
    }

    public function decrementer(Request $request, Pharmacie $pharmacie, Produit $produit)
    {
        $request->validate([
            'quantite' => 'required|integer|min:1'
        ]);

        $stock = $pharmacie->produits()->where('produit_id', $produit->id)->first();
        $stockActuel = $stock->pivot->quantite_disponible ?? 0;
        $nouvelleQuantite = max(0, $stockActuel - $request->quantite);

        $pharmacie->produits()->updateExistingPivot($produit->id, [
            'quantite_disponible' => $nouvelleQuantite
        ]);

        return response()->json(['message' => 'Stock décrémenté', 'nouveau_stock' => $nouvelleQuantite]);
    }
}