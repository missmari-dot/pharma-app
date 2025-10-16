<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicament;
use App\Models\Produit;
use Illuminate\Http\Request;

class MedicamentController extends Controller
{
    public function index()
    {
        return Medicament::with('produit')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom_produit' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix' => 'required|numeric|min:0',
            'image' => 'nullable|string',
            'necessite_ordonnance' => 'boolean',
            'posologie' => 'nullable|string'
        ]);

        $produit = Produit::create([
            'nom_produit' => $validated['nom_produit'],
            'description' => $validated['description'],
            'prix' => $validated['prix'],
            'image' => $validated['image'],
            'categorie' => 'Médicament',
            'necessite_ordonnance' => $validated['necessite_ordonnance'] ?? true
        ]);

        $medicament = Medicament::create([
            'produit_id' => $produit->id,
            'posologie' => $validated['posologie']
        ]);

        return $medicament->load('produit');
    }

    public function show(Medicament $medicament)
    {
        return $medicament->load('produit');
    }

    public function update(Request $request, Medicament $medicament)
    {
        $validated = $request->validate([
            'posologie' => 'nullable|string'
        ]);

        $medicament->update($validated);
        return $medicament->load('produit');
    }

    public function destroy(Medicament $medicament)
    {
        $medicament->produit->delete(); // Cascade delete
        return response()->json(['message' => 'Médicament supprimé']);
    }
}