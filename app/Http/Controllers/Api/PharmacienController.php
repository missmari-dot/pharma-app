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
        $user = $request->user();
        $pharmacien = $user->pharmacien;
        
        if (!$pharmacien) {
            // Créer le profil pharmacien s'il n'existe pas
            $pharmacien = \App\Models\Pharmacien::create([
                'user_id' => $user->id
            ]);
        }
        
        // Vérifier si une pharmacie existe déjà
        $pharmacieExistante = $pharmacien->pharmacies()->first();
        if ($pharmacieExistante && $pharmacieExistante->statut_validation === 'approved') {
            return response()->json(['message' => 'Vous avez déjà une pharmacie validée'], 400);
        }
        
        // Si rejetée, permettre une nouvelle demande en mettant à jour
        if ($pharmacieExistante && $pharmacieExistante->statut_validation === 'rejected') {
            return $this->mettreAJourDemande($request, $pharmacieExistante);
        }
        
        // Si en attente, interdire nouvelle demande
        if ($pharmacieExistante && $pharmacieExistante->statut_validation === 'pending') {
            return response()->json(['message' => 'Demande en cours de traitement'], 400);
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
            'longitude' => 'nullable|numeric',
            'est_de_garde' => 'nullable|boolean'
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
            'est_de_garde' => $validated['est_de_garde'] ?? false,
            'pharmacien_id' => $pharmacien->id,
            'statut_validation' => 'pending'
        ]);

        return response()->json([
            'message' => 'Pharmacie enregistrée. En attente de validation.',
            'pharmacie' => $pharmacie
        ]);
    }
    
    private function mettreAJourDemande(Request $request, $pharmacieExistante)
    {
        $validated = $request->validate([
            'nom_pharmacie' => 'required|string|max:255',
            'adresse_pharmacie' => 'required|string',
            'telephone_pharmacie' => 'required|string|max:20',
            'numero_agrement' => 'required|string',
            'documents_justificatifs' => 'nullable|file|mimes:pdf|max:10240',
            'heure_ouverture' => 'required|date_format:H:i',
            'heure_fermeture' => 'required|date_format:H:i',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'est_de_garde' => 'nullable|boolean'
        ]);

        $updateData = [
            'nom_pharmacie' => $validated['nom_pharmacie'],
            'adresse_pharmacie' => $validated['adresse_pharmacie'],
            'telephone_pharmacie' => $validated['telephone_pharmacie'],
            'numero_agrement' => $validated['numero_agrement'],
            'heure_ouverture' => $validated['heure_ouverture'],
            'heure_fermeture' => $validated['heure_fermeture'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'est_de_garde' => $validated['est_de_garde'] ?? false,
            'statut_validation' => 'pending'
        ];

        if ($request->hasFile('documents_justificatifs')) {
            $documentsPath = $request->file('documents_justificatifs')
                ->store('pharmacies/documents', 'public');
            $updateData['documents_justificatifs'] = $documentsPath;
        }
        


        $pharmacieExistante->update($updateData);

        return response()->json([
            'message' => 'Nouvelle demande soumise après rejet. En attente de validation.',
            'pharmacie' => $pharmacieExistante
        ]);
    }
}