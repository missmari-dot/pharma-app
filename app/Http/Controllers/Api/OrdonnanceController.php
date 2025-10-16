<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ordonnance;
use Illuminate\Http\Request;

class OrdonnanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Ordonnance::with(['client', 'pharmacie']);

        if ($request->user()->role === 'client') {
            $query->where('client_id', $request->user()->id);
        } elseif ($request->user()->role === 'pharmacien') {
            $pharmacien = $request->user()->pharmacien;
            if ($pharmacien && $pharmacien->pharmacie) {
                $query->where('pharmacie_id', $pharmacien->pharmacie->id);
            }
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pharmacie_id' => 'required|exists:pharmacies,id',
            'photo_ordonnance' => 'required|image|max:2048|mimes:jpeg,png,jpg',
            'commentaire' => 'nullable|string|max:500'
        ]);

        // Vérifier que l'utilisateur est un client
        if ($request->user()->role !== 'client') {
            return response()->json(['message' => 'Seuls les clients peuvent envoyer des ordonnances'], 403);
        }

        // Vérifier que le client existe
        $client = $request->user()->client;
        if (!$client) {
            return response()->json(['message' => 'Profil client non trouvé'], 404);
        }

        $photoPath = $request->file('photo_ordonnance')->store('ordonnances', 'public');

        $ordonnance = Ordonnance::create([
            'client_id' => $client->id,
            'pharmacie_id' => $validated['pharmacie_id'],
            'photo_url' => $photoPath,
            'statut' => 'ENVOYEE',
            'date_envoi' => now(),
            'commentaire' => $validated['commentaire'] ?? null
        ]);

        // Notifier le pharmacien
        $notificationService = new \App\Services\NotificationService();
        $notificationService->notifierOrdonnanceRecue($ordonnance);

        return response()->json([
            'message' => 'Ordonnance envoyée avec succès',
            'ordonnance' => $ordonnance->load(['client', 'pharmacie'])
        ], 201);
    }

    public function show(Ordonnance $ordonnance)
    {
        return $ordonnance->load(['client', 'pharmacie', 'reservation']);
    }

    public function valider(Request $request, Ordonnance $ordonnance)
    {
        if ($request->user()->role !== 'pharmacien') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validated = $request->validate([
            'remarque_pharmacien' => 'nullable|string|max:500'
        ]);

        $ordonnance->update([
            'statut' => 'VALIDEE',
            'date_traitement' => now(),
            'remarque_pharmacien' => $validated['remarque_pharmacien'] ?? null
        ]);

        // Notifier le client
        $notificationService = new \App\Services\NotificationService();
        $notificationService->notifierOrdonnanceValidee($ordonnance);

        return response()->json([
            'message' => 'Ordonnance validée avec succès',
            'ordonnance' => $ordonnance->load(['client', 'pharmacie'])
        ]);
    }

    public function rejeter(Request $request, Ordonnance $ordonnance)
    {
        if ($request->user()->role !== 'pharmacien') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validated = $request->validate([
            'commentaire' => 'required|string|max:500'
        ]);

        $ordonnance->update([
            'statut' => 'REJETEE',
            'date_traitement' => now(),
            'commentaire' => $validated['commentaire']
        ]);

        // Notifier le client du rejet
        $notificationService = new \App\Services\NotificationService();
        $notificationService->notifierOrdonnanceRejetee($ordonnance);

        return response()->json([
            'message' => 'Ordonnance rejetée',
            'ordonnance' => $ordonnance->load(['client', 'pharmacie'])
        ]);
    }
}
