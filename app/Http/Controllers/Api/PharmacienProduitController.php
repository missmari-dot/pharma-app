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
            
        // Ajouter le stock depuis le pivot et l'URL de l'image
        $produits->getCollection()->transform(function ($produit) {
            $produit->stock = $produit->pivot->quantite_disponible;
            $produit->image_url = $produit->image ? asset('storage/' . $produit->image) : null;
            return $produit;
        });
        
        return $produits;
    }

    public function genererNouveauCode()
    {
        $nextId = Produit::max('id') + 1;
        $codeProduit = 'PROD' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
        
        return response()->json([
            'code_produit' => $codeProduit
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom_produit' => 'required|string|min:1|max:255',
            'description' => 'nullable|string|max:1000',
            'prix' => 'required|numeric|min:0.01',
            'categorie' => 'required|in:Médicament,Parapharmacie',
            'quantite_disponible' => 'required|integer|min:1',
            'necessite_ordonnance' => 'sometimes|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ], [
            'nom_produit.required' => 'Le nom du produit est obligatoire',
            'nom_produit.min' => 'Le nom du produit ne peut pas être vide',
            'prix.required' => 'Le prix est obligatoire',
            'prix.min' => 'Le prix doit être supérieur à 0',
            'categorie.required' => 'La catégorie est obligatoire',
            'categorie.in' => 'La catégorie doit être Médicament ou Parapharmacie',
            'quantite_disponible.required' => 'La quantité est obligatoire',
            'quantite_disponible.min' => 'La quantité doit être au moins 1'
        ]);
        
        $pharmacien = $request->user()->pharmacien;
        if (!$pharmacien) {
            return response()->json(['error' => 'Profil pharmacien non trouvé'], 404);
        }
        
        $pharmacie = $pharmacien->pharmacies()->first();
        if (!$pharmacie) {
            return response()->json(['error' => 'Aucune pharmacie associée'], 404);
        }

        // Générer un code produit unique
        $nextId = Produit::max('id') + 1;
        $codeProduit = 'PROD' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
        
        // Gérer l'upload de l'image
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('produits', 'public');
        }
        
        // Créer le produit avec tous les champs
        $produit = Produit::create([
            'code_produit' => $codeProduit,
            'nom_produit' => $validated['nom_produit'],
            'description' => $validated['description'] ?? null,
            'prix' => $validated['prix'],
            'categorie' => $validated['categorie'],
            'image' => $imagePath,
            'necessite_ordonnance' => $validated['necessite_ordonnance'] ?? false
        ]);
        
        // Associer à la pharmacie
        $pharmacie->produits()->attach($produit->id, [
            'quantite_disponible' => $validated['quantite_disponible']
        ]);

        // Retourner le produit avec le stock
        $produit->stock = $validated['quantite_disponible'];
        return response()->json($produit, 201);
    }

    public function update(Request $request, Produit $produit)
    {
        // Vérifier qu'au moins un champ est présent
        if (!$request->hasAny(['nom_produit', 'prix', 'categorie', 'quantite_disponible'])) {
            return response()->json([
                'message' => 'Aucune donnée à modifier',
                'errors' => ['general' => ['Veuillez remplir au moins un champ']]
            ], 422);
        }

        $validated = $request->validate([
            'nom_produit' => 'filled|string|min:1|max:255',
            'prix' => 'filled|numeric|min:0.01',
            'categorie' => 'filled|in:Médicament,Parapharmacie',
            'quantite_disponible' => 'filled|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ], [
            'nom_produit.filled' => 'Le nom du produit ne peut pas être vide',
            'nom_produit.min' => 'Le nom du produit doit contenir au moins 1 caractère',
            'prix.filled' => 'Le prix ne peut pas être vide',
            'prix.min' => 'Le prix doit être supérieur à 0',
            'categorie.filled' => 'La catégorie ne peut pas être vide',
            'categorie.in' => 'La catégorie doit être Médicament ou Parapharmacie',
            'quantite_disponible.filled' => 'La quantité ne peut pas être vide'
        ]);

        $pharmacie = $request->user()->pharmacien->pharmacies()->first();
        
        // Gérer l'upload de l'image
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('produits', 'public');
            $validated['image'] = $imagePath;
        }
        
        $produit->update(array_intersect_key($validated, array_flip(['nom_produit', 'prix', 'categorie', 'image'])));
        
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
        
        // Vérifier que le pharmacien possède ce produit
        if (!$pharmacie->produits()->where('produit_id', $produit->id)->exists()) {
            return response()->json(['error' => 'Produit non trouvé dans votre pharmacie'], 404);
        }
        
        // Supprimer complètement le produit de la base de données
        $produit->delete();
        
        return response()->json(['message' => 'Produit supprimé définitivement']);
    }
}