<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConseilSante;
use Illuminate\Http\Request;

class ConseilSanteController extends Controller
{
    public function index(Request $request)
    {
        $query = ConseilSante::with('pharmacien');

        // Filtrage par catégorie
        if ($request->has('categorie')) {
            $query->where('categorie', $request->categorie);
        }

        // Recherche par titre ou contenu
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                  ->orWhere('contenu', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('date_publication', 'desc')
                    ->paginate($request->get('per_page', 15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string|min:50',
            'categorie' => 'nullable|string|max:100'
        ]);

        // Vérifier que l'utilisateur est un pharmacien
        if ($request->user()->role !== 'pharmacien') {
            return response()->json(['message' => 'Seuls les pharmaciens peuvent publier des conseils'], 403);
        }

        $pharmacien = $request->user()->pharmacien;
        if (!$pharmacien) {
            return response()->json(['message' => 'Profil pharmacien non trouvé'], 404);
        }

        $conseil = ConseilSante::create([
            'titre' => $validated['titre'],
            'contenu' => $validated['contenu'],
            'categorie' => $validated['categorie'] ?? 'Général',
            'pharmacien_id' => $pharmacien->id,
            'date_publication' => now()
        ]);

        return response()->json([
            'message' => 'Conseil de santé publié avec succès',
            'conseil' => $conseil->load('pharmacien')
        ], 201);
    }

    public function show(ConseilSante $conseilSante)
    {
        return $conseilSante->load('pharmacien');
    }

    public function update(Request $request, ConseilSante $conseilSante)
    {
        // Vérifier que l'utilisateur est le propriétaire du conseil
        if ($request->user()->role !== 'pharmacien' ||
            $request->user()->pharmacien->id !== $conseilSante->pharmacien_id) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validated = $request->validate([
            'titre' => 'string|max:255',
            'contenu' => 'string|min:50',
            'categorie' => 'string|max:100'
        ]);

        $conseilSante->update($validated);

        return response()->json([
            'message' => 'Conseil mis à jour avec succès',
            'conseil' => $conseilSante->load('pharmacien')
        ]);
    }

    public function destroy(Request $request, ConseilSante $conseilSante)
    {
        // Vérifier que l'utilisateur est le propriétaire du conseil
        if ($request->user()->role !== 'pharmacien' ||
            $request->user()->pharmacien->id !== $conseilSante->pharmacien_id) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $conseilSante->delete();
        return response()->json(['message' => 'Conseil supprimé avec succès']);
    }

    public function mesConseils(Request $request)
    {
        $pharmacien = $request->user()->pharmacien;
        if (!$pharmacien) {
            return response()->json(['message' => 'Profil pharmacien non trouvé'], 404);
        }

        $conseils = ConseilSante::where('pharmacien_id', $pharmacien->id)
            ->orderBy('date_publication', 'desc')
            ->paginate($request->get('per_page', 15));

        return $conseils;
    }
}
