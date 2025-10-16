<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProduitParapharmacie;
use App\Models\Produit;
use Illuminate\Http\Request;

class ProduitParapharmacieController extends Controller
{
    public function index()
    {
        return ProduitParapharmacie::with('produit')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom_produit' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix' => 'required|numeric|min:0',
            'image' => 'nullable|string',
            'marque' => 'nullable|string',
            'categorie_parapharmacie' => 'nullable|string'
        ]);

        $produit = Produit::create([
            'nom_produit' => $validated['nom_produit'],
            'description' => $validated['description'],
            'prix' => $validated['prix'],
            'image' => $validated['image'],
            'categorie' => 'Parapharmacie',
            'necessite_ordonnance' => false
        ]);

        $produitParapharmacie = ProduitParapharmacie::create([
            'produit_id' => $produit->id,
            'marque' => $validated['marque'],
            'categorie_parapharmacie' => $validated['categorie_parapharmacie']
        ]);

        return $produitParapharmacie->load('produit');
    }

    public function show(ProduitParapharmacie $produitParapharmacie)
    {
        return $produitParapharmacie->load('produit');
    }

    public function update(Request $request, ProduitParapharmacie $produitParapharmacie)
    {
        $validated = $request->validate([
            'marque' => 'nullable|string',
            'categorie_parapharmacie' => 'nullable|string'
        ]);

        $produitParapharmacie->update($validated);
        return $produitParapharmacie->load('produit');
    }

    public function destroy(ProduitParapharmacie $produitParapharmacie)
    {
        $produitParapharmacie->produit->delete(); // Cascade delete
        return response()->json(['message' => 'Produit parapharmacie supprimé']);
    }
}