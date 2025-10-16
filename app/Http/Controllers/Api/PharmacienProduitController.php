<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use Illuminate\Http\Request;

class PharmacienProduitController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }
        
        $pharmacien = $user->pharmacien;
        if (!$pharmacien) {
            return response()->json(['error' => 'Profil pharmacien non trouvé'], 404);
        }
        
        $pharmacie = $pharmacien->pharmacies()->first();
        if (!$pharmacie) {
            return response()->json(['error' => 'Aucune pharmacie associée'], 404);
        }
        
        $produits = $pharmacie->produits()
            ->withPivot('quantite_disponible')
            ->paginate(20);
            
        // Ajouter le stock depuis le pivot
        $produits->getCollection()->transform(function ($produit) {
            $produit->stock = $produit->pivot->quantite_disponible;
            return $produit;
        });
        
        return $produits;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom_produit' => 'required|string',
            'prix' => 'required|numeric|min:0',
            'type_produit' => 'required|in:MEDICAMENT,PARAPHARMACIE',
            'quantite_disponible' => 'required|integer|min:0'
        ]);
        
        $validated['categorie'] = $validated['type_produit'] === 'MEDICAMENT' ? 'Médicament' : 'Parapharmacie';

        $pharmacien = $request->user()->pharmacien;
        if (!$pharmacien) {
            return response()->json(['error' => 'Profil pharmacien non trouvé'], 404);
        }
        
        $pharmacie = $pharmacien->pharmacies()->first();
        if (!$pharmacie) {
            return response()->json(['error' => 'Aucune pharmacie associée'], 404);
        }

        $produit = Produit::create([
            'nom_produit' => $validated['nom_produit'],
            'prix' => $validated['prix'],
            'type_produit' => $validated['type_produit'],
            'categorie' => $validated['categorie']
        ]);
        
        $pharmacie->produits()->attach($produit->id, [
            'quantite_disponible' => $validated['quantite_disponible']
        ]);

        return response()->json($produit, 201);
    }

    public function update(Request $request, Produit $produit)
    {
        $validated = $request->validate([
            'nom_produit' => 'sometimes|string',
            'prix' => 'sometimes|numeric|min:0',
            'quantite_disponible' => 'sometimes|integer|min:0'
        ]);

        $pharmacie = $request->user()->pharmacien->pharmacies()->first();
        
        $produit->update(array_intersect_key($validated, array_flip(['nom_produit', 'prix'])));
        
        if (isset($validated['quantite_disponible'])) {
            $pharmacie->produits()->updateExistingPivot($produit->id, [
                'quantite_disponible' => $validated['quantite_disponible']
            ]);
        }

        return response()->json($produit);
    }

    public function destroy(Request $request, Produit $produit)
    {
        $pharmacie = $request->user()->pharmacien->pharmacies()->first();
        $pharmacie->produits()->detach($produit->id);
        
        return response()->json(['message' => 'Produit retiré']);
    }
}