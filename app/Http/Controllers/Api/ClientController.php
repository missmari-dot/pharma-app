<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function show(Request $request)
    {
        if ($request->user()->role !== 'client') {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        
        $client = $request->user()->client;
        
        if (!$client) {
            return response()->json(['message' => 'Profil client non trouvé'], 404);
        }

        return $client->load('user');
    }

    public function update(Request $request)
    {
        $client = $request->user()->client;
        
        if (!$client) {
            return response()->json(['message' => 'Profil client non trouvé'], 404);
        }

        $validated = $request->validate([
            'adresse' => 'nullable|string',
            'date_naissance' => 'nullable|date'
        ]);

        $client->update($validated);
        return $client->load('user');
    }

    public function mesReservations(Request $request)
    {
        $client = $request->user()->client;
        
        if (!$client) {
            return response()->json(['message' => 'Profil client non trouvé'], 404);
        }

        return $client->reservations()
            ->with(['pharmacie', 'lignesReservation.produit'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function mesOrdonnances(Request $request)
    {
        $client = $request->user()->client;
        
        if (!$client) {
            return response()->json(['message' => 'Profil client non trouvé'], 404);
        }

        return $client->ordonnances()
            ->with('pharmacie')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}