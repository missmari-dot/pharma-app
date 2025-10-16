<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pharmacien;
use Illuminate\Http\Request;

class PharmacienController extends Controller
{
    public function show(Request $request)
    {
        $pharmacien = $request->user()->pharmacien;
        
        if (!$pharmacien) {
            return response()->json(['message' => 'Profil pharmacien non trouvé'], 404);
        }

        return $pharmacien->load(['user', 'pharmacies']);
    }

    public function update(Request $request)
    {
        $pharmacien = $request->user()->pharmacien;
        
        if (!$pharmacien) {
            return response()->json(['message' => 'Profil pharmacien non trouvé'], 404);
        }

        $validated = $request->validate([
            'pharmacies_associees' => 'nullable|string'
        ]);

        $pharmacien->update($validated);
        return $pharmacien->load(['user', 'pharmacies']);
    }

    public function mesPharmacies(Request $request)
    {
        $pharmacien = $request->user()->pharmacien;
        
        if (!$pharmacien) {
            return response()->json(['message' => 'Profil pharmacien non trouvé'], 404);
        }

        return $pharmacien->pharmacies()->with('produits')->get();
    }

    public function mesConseils(Request $request)
    {
        $pharmacien = $request->user()->pharmacien;
        
        if (!$pharmacien) {
            return response()->json(['message' => 'Profil pharmacien non trouvé'], 404);
        }

        return $pharmacien->conseilsSante()
            ->orderBy('date_publication', 'desc')
            ->get();
    }

    public function reservationsRecues(Request $request)
    {
        $pharmacien = $request->user()->pharmacien;
        
        if (!$pharmacien) {
            return response()->json(['message' => 'Profil pharmacien non trouvé'], 404);
        }

        $pharmacies = $pharmacien->pharmacies()->pluck('id');
        
        return \App\Models\Reservation::whereIn('pharmacie_id', $pharmacies)
            ->with(['client.user', 'pharmacie', 'lignesReservation.produit'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function enregistrerPharmacie(Request $request)
    {
        $pharmacien = $request->user()->pharmacien;
        
        if (!$pharmacien || $request->user()->statut !== 'approved') {
            return response()->json(['message' => 'Compte non validé'], 403);
        }

        $validated = $request->validate([
            'nom_pharmacie' => 'required|string|max:255',
            'adresse_pharmacie' => 'required|string',
            'telephone_pharmacie' => 'required|string|max:20',
            'numero_agrement' => 'required|string',
            'documents_justificatifs' => 'required|file|mimes:pdf|max:10240',
            'heure_ouverture' => 'required|date_format:H:i',
            'heure_fermeture' => 'required|date_format:H:i',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric'
        ]);

        $documentsPath = null;
        if ($request->hasFile('documents_justificatifs')) {
            $documentsPath = $request->file('documents_justificatifs')
                ->store('pharmacies/documents', 'public');
        }

        $pharmacie = \App\Models\Pharmacie::create([
            'nom_pharmacie' => $validated['nom_pharmacie'],
            'adresse_pharmacie' => $validated['adresse_pharmacie'],
            'telephone_pharmacie' => $validated['telephone_pharmacie'],
            'numero_agrement' => $validated['numero_agrement'],
            'documents_justificatifs' => $documentsPath,
            'heure_ouverture' => $validated['heure_ouverture'],
            'heure_fermeture' => $validated['heure_fermeture'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'pharmacien_id' => $pharmacien->id,
            'statut' => 'pending'
        ]);

        return response()->json([
            'message' => 'Pharmacie enregistrée. En attente de validation.',
            'pharmacie' => $pharmacie
        ]);
    }
}