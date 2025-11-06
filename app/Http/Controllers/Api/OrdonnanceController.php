<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ordonnance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrdonnanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Ordonnance::with(['client', 'pharmacie']);

        if ($request->user()->role === 'client') {
            $query->where('client_id', $request->user()->id);
        } elseif ($request->user()->role === 'pharmacien') {
            $pharmacien = $request->user()->pharmacien;
            if ($pharmacien && $pharmacien->pharmacies->count() > 0) {
                $query->whereIn('pharmacie_id', $pharmacien->pharmacies->pluck('id'));
            }
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pharmacie_id' => 'required|exists:pharmacies,id',
            'photo_ordonnance' => 'required',
            'commentaire' => 'nullable|string|max:500'
        ]);
        
        // Vérifier que la pharmacie est validée
        $pharmacie = \App\Models\Pharmacie::find($validated['pharmacie_id']);
        if ($pharmacie->statut_validation !== 'approved') {
            return response()->json(['message' => 'Cette pharmacie n\'est pas encore validée'], 403);
        }

        if ($request->user()->role !== 'client') {
            return response()->json(['message' => 'Seuls les clients peuvent envoyer des ordonnances'], 403);
        }

        $client = $request->user()->client;
        if (!$client) {
            return response()->json(['message' => 'Profil client non trouvé'], 404);
        }

        // Handle both file upload and base64
        if ($request->hasFile('photo_ordonnance')) {
            // File upload from mobile
            $photoPath = $request->file('photo_ordonnance')->store('ordonnances', 'public');
        } else {
            // Base64 string
            $imageData = $validated['photo_ordonnance'];
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $type = strtolower($type[1]);
                
                if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
                    return response()->json(['message' => 'Format d\'image non supporté'], 422);
                }
                
                $imageData = base64_decode($imageData);
                $fileName = 'ordonnance_' . time() . '.' . $type;
                $photoPath = 'ordonnances/' . $fileName;
                
                Storage::disk('public')->put($photoPath, $imageData);
            } else {
                return response()->json(['message' => 'Format d\'image invalide'], 422);
            }
        }

        $ordonnance = Ordonnance::create([
            'client_id' => $client->id,
            'pharmacie_id' => $validated['pharmacie_id'],
            'photo_url' => $photoPath,
            'statut' => 'envoyee',
            'date_envoi' => now(),
            'commentaire' => $validated['commentaire'] ?? null
        ]);

        return response()->json([
            'message' => 'Ordonnance envoyée avec succès',
            'ordonnance' => $ordonnance->load(['client', 'pharmacie'])
        ], 201);
    }

    public function show(Ordonnance $ordonnance)
    {
        return $ordonnance->load(['client', 'pharmacie', 'reservation']);
    }

    public function uploadImage(Request $request)
    {
        $validated = $request->validate([
            'pharmacie_id' => 'required|exists:pharmacies,id',
            'ordonnance_image' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);
        
        // Vérifier que la pharmacie est validée
        $pharmacie = \App\Models\Pharmacie::find($validated['pharmacie_id']);
        if ($pharmacie->statut_validation !== 'approved') {
            return response()->json(['message' => 'Cette pharmacie n\'est pas encore validée'], 403);
        }

        if ($request->user()->role !== 'client') {
            return response()->json(['message' => 'Seuls les clients peuvent envoyer des ordonnances'], 403);
        }

        $client = $request->user()->client;
        if (!$client) {
            return response()->json(['message' => 'Profil client non trouvé'], 404);
        }

        $imagePath = $request->file('ordonnance_image')->store('ordonnances', 'public');

        $ordonnance = Ordonnance::create([
            'client_id' => $client->id,
            'pharmacie_id' => $validated['pharmacie_id'],
            'image_ordonnance' => $imagePath,
            'statut' => 'en_attente',
            'date_prescription' => now()
        ]);

        return response()->json([
            'message' => 'Ordonnance uploadée avec succès',
            'ordonnance' => $ordonnance
        ], 201);
    }

    public function envoyerSansMedicaments(Request $request)
    {
        $validated = $request->validate([
            'pharmacie_id' => 'required|exists:pharmacies,id',
            'photo_ordonnance' => 'required',
            'commentaire' => 'nullable|string|max:500'
        ]);
        
        $pharmacie = \App\Models\Pharmacie::find($validated['pharmacie_id']);
        if ($pharmacie->statut_validation !== 'approved') {
            return response()->json(['message' => 'Cette pharmacie n\'est pas encore validée'], 403);
        }

        if ($request->user()->role !== 'client') {
            return response()->json(['message' => 'Seuls les clients peuvent envoyer des ordonnances'], 403);
        }

        $client = $request->user()->client;
        if (!$client) {
            return response()->json(['message' => 'Profil client non trouvé'], 404);
        }

        // Gérer l'upload de l'image
        if ($request->hasFile('photo_ordonnance')) {
            $photoPath = $request->file('photo_ordonnance')->store('ordonnances', 'public');
        } else {
            $imageData = $validated['photo_ordonnance'];
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $type = strtolower($type[1]);
                
                if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
                    return response()->json(['message' => 'Format d\'image non supporté'], 422);
                }
                
                $imageData = base64_decode($imageData);
                $fileName = 'ordonnance_' . time() . '.' . $type;
                $photoPath = 'ordonnances/' . $fileName;
                
                Storage::disk('public')->put($photoPath, $imageData);
            } else {
                return response()->json(['message' => 'Format d\'image invalide'], 422);
            }
        }

        $ordonnance = Ordonnance::create([
            'client_id' => $client->id,
            'pharmacie_id' => $validated['pharmacie_id'],
            'photo_url' => $photoPath,
            'statut' => 'en_attente',
            'date_envoi' => now(),
            'commentaire' => $validated['commentaire'] ?? null
        ]);

        return response()->json([
            'message' => 'Ordonnance envoyée avec succès. Le pharmacien va analyser et sélectionner les médicaments.',
            'ordonnance' => $ordonnance->load(['client', 'pharmacie'])
        ], 201);
    }

    public function traiterOrdonnance(Request $request, Ordonnance $ordonnance)
    {
        if ($request->user()->role !== 'pharmacien') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $pharmacien = $request->user()->pharmacien;
        if (!$pharmacien->pharmacies->contains($ordonnance->pharmacie_id)) {
            return response()->json(['message' => 'Vous n\'avez pas accès à cette ordonnance'], 403);
        }

        $validated = $request->validate([
            'medicaments' => 'required|array|min:1',
            'medicaments.*.produit_id' => 'required|exists:produits,id',
            'medicaments.*.quantite_prescrite' => 'required|integer|min:1',
            'medicaments.*.dosage' => 'nullable|string|max:100',
            'medicaments.*.instructions' => 'nullable|string|max:500',
            'commentaire' => 'nullable|string|max:500'
        ]);

        // Vérifier que tous les produits sont disponibles
        foreach ($validated['medicaments'] as $medicament) {
            $produit = \App\Models\Produit::find($medicament['produit_id']);
            $stock = $ordonnance->pharmacie->produits()->where('produit_id', $medicament['produit_id'])->first();
            
            if (!$stock || $stock->pivot->quantite_disponible < $medicament['quantite_prescrite']) {
                return response()->json([
                    'error' => "Stock insuffisant pour {$produit->nom_produit}"
                ], 400);
            }
        }

        // Créer les lignes d'ordonnance
        foreach ($validated['medicaments'] as $medicament) {
            $ordonnance->lignesOrdonnance()->create([
                'produit_id' => $medicament['produit_id'],
                'quantite_prescrite' => $medicament['quantite_prescrite'],
                'dosage' => $medicament['dosage'] ?? null,
                'instructions' => $medicament['instructions'] ?? null,
                'statut' => 'validee'
            ]);
        }

        $ordonnance->update([
            'statut' => 'validee',
            'commentaire' => $validated['commentaire'] ?? $ordonnance->commentaire
        ]);

        // Créer automatiquement une réservation
        $codeRetrait = 'RET-' . strtoupper(substr(md5(uniqid()), 0, 6));
        $reservation = \App\Models\Reservation::create([
            'client_id' => $ordonnance->client_id,
            'pharmacie_id' => $ordonnance->pharmacie_id,
            'ordonnance_id' => $ordonnance->id,
            'date_reservation' => now(),
            'code_retrait' => $codeRetrait,
            'statut' => 'en_attente',
            'montant_total' => 0
        ]);

        // Créer les lignes de réservation et calculer le montant
        $montantTotal = 0;
        foreach ($validated['medicaments'] as $medicament) {
            $produit = \App\Models\Produit::find($medicament['produit_id']);
            $ligneReservation = $reservation->lignesReservation()->create([
                'produit_id' => $medicament['produit_id'],
                'quantite_reservee' => $medicament['quantite_prescrite'],
                'prix_unitaire' => $produit->prix
            ]);
            $montantTotal += $ligneReservation->getSousTotal();
        }

        $reservation->update(['montant_total' => $montantTotal]);

        return response()->json([
            'message' => 'Ordonnance traitée avec succès',
            'ordonnance' => $ordonnance->load(['client', 'pharmacie', 'lignesOrdonnance.produit']),
            'reservation' => $reservation->load(['lignesReservation.produit']),
            'code_retrait' => $codeRetrait
        ]);
    }

    public function creerReservationAvecProduits(Request $request, Ordonnance $ordonnance)
    {
        if ($request->user()->role !== 'pharmacien') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $pharmacien = $request->user()->pharmacien;
        if (!$pharmacien->pharmacies->contains($ordonnance->pharmacie_id)) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        if ($ordonnance->statut !== 'envoyee' && $ordonnance->statut !== 'validee') {
            return response()->json(['message' => 'Cette ordonnance a déjà été traitée'], 400);
        }

        // Si l'ordonnance est validée mais n'a pas de réservation, permettre le retraitement
        if ($ordonnance->statut === 'validee' && $ordonnance->reservation) {
            return response()->json(['message' => 'Cette ordonnance a déjà une réservation'], 400);
        }

        $validated = $request->validate([
            'produits' => 'required|array|min:1',
            'produits.*.produit_id' => 'required|exists:produits,id',
            'produits.*.quantite' => 'required|integer|min:1',
            'remarque_pharmacien' => 'nullable|string|max:500'
        ]);

        // Vérifier les stocks
        foreach ($validated['produits'] as $produitData) {
            $produit = \App\Models\Produit::find($produitData['produit_id']);
            $stock = $ordonnance->pharmacie->produits()->where('produit_id', $produitData['produit_id'])->first();
            
            if (!$stock || $stock->pivot->quantite_disponible < $produitData['quantite']) {
                return response()->json([
                    'error' => "Stock insuffisant pour {$produit->nom_produit}"
                ], 400);
            }
        }

        // Marquer l'ordonnance comme validée
        $ordonnance->update([
            'statut' => 'validee',
            'date_traitement' => now(),
            'remarque_pharmacien' => $validated['remarque_pharmacien'] ?? null
        ]);

        // Créer la réservation directement confirmée
        $codeRetrait = 'RET-' . strtoupper(substr(md5(uniqid()), 0, 6));
        $reservation = \App\Models\Reservation::create([
            'client_id' => $ordonnance->client_id,
            'pharmacie_id' => $ordonnance->pharmacie_id,
            'ordonnance_id' => $ordonnance->id,
            'date_reservation' => now(),
            'date_expiration' => now()->addHours(24),
            'code_retrait' => $codeRetrait,
            'statut' => 'confirmee',
            'montant_total' => 0
        ]);

        // Ajouter les produits et calculer le montant
        $montantTotal = 0;
        foreach ($validated['produits'] as $produitData) {
            $produit = \App\Models\Produit::find($produitData['produit_id']);
            $ligneReservation = $reservation->lignesReservation()->create([
                'produit_id' => $produitData['produit_id'],
                'quantite_reservee' => $produitData['quantite'],
                'prix_unitaire' => $produit->prix
            ]);
            $montantTotal += $ligneReservation->getSousTotal();
        }

        $reservation->update(['montant_total' => $montantTotal]);

        // Notification au client
        try {
            $client = $ordonnance->client;
            if ($client && $client->fcm_token) {
                $this->envoyerNotificationPush($client->fcm_token, [
                    'title' => 'Réservation prête',
                    'body' => 'Vos médicaments sont prêts. Code de retrait: ' . $codeRetrait,
                    'data' => ['reservation_id' => $reservation->id]
                ]);
            }
        } catch (\Exception $e) {
            // Log l'erreur
        }

        return response()->json([
            'message' => 'Réservation créée avec succès',
            'ordonnance' => $ordonnance->load(['client', 'pharmacie']),
            'reservation' => $reservation->load(['lignesReservation.produit']),
            'code_retrait' => $codeRetrait
        ]);
    }

    public function valider(Request $request, Ordonnance $ordonnance)
    {
        if ($request->user()->role !== 'pharmacien') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validated = $request->validate([
            'remarque_pharmacien' => 'nullable|string|max:500',
            'produits' => 'nullable|array',
            'produits.*.produit_id' => 'required_with:produits|exists:produits,id',
            'produits.*.quantite' => 'required_with:produits|integer|min:1'
        ]);

        $ordonnance->update([
            'statut' => 'validee',
            'date_traitement' => now(),
            'remarque_pharmacien' => $validated['remarque_pharmacien'] ?? null
        ]);

        // Créer automatiquement une réservation avec code de retrait
        $codeRetrait = 'RET-' . strtoupper(substr(md5(uniqid()), 0, 6));
        $reservation = \App\Models\Reservation::create([
            'client_id' => $ordonnance->client_id,
            'pharmacie_id' => $ordonnance->pharmacie_id,
            'ordonnance_id' => $ordonnance->id,
            'date_reservation' => now(),
            'date_expiration' => now()->addHours(24),
            'code_retrait' => $codeRetrait,
            'statut' => 'en_attente',
            'montant_total' => 0
        ]);

        // Ajouter les produits spécifiés par le pharmacien
        $montantTotal = 0;
        if (isset($validated['produits'])) {
            foreach ($validated['produits'] as $produitData) {
                $produit = \App\Models\Produit::find($produitData['produit_id']);
                $ligneReservation = $reservation->lignesReservation()->create([
                    'produit_id' => $produitData['produit_id'],
                    'quantite_reservee' => $produitData['quantite'],
                    'prix_unitaire' => $produit->prix
                ]);
                $montantTotal += $ligneReservation->getSousTotal();
            }
            $reservation->update(['montant_total' => $montantTotal]);
        }

        // Notification push au client
        try {
            $client = $ordonnance->client;
            if ($client && $client->fcm_token) {
                $this->envoyerNotificationPush($client->fcm_token, [
                    'title' => 'Ordonnance validée',
                    'body' => 'Votre ordonnance est prête. Code: ' . $codeRetrait,
                    'data' => ['reservation_id' => $reservation->id]
                ]);
            }
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas faire échouer la validation
        }

        return response()->json([
            'message' => 'Ordonnance validée et réservation créée',
            'ordonnance' => $ordonnance->load(['client', 'pharmacie']),
            'reservation' => $reservation->load(['lignesReservation.produit']),
            'code_retrait' => $codeRetrait
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
            'statut' => 'rejetee',
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

    public function enAttente(Request $request)
    {
        $query = Ordonnance::with(['client', 'pharmacie'])
            ->where('statut', 'envoyee');

        if ($request->user()->role === 'client') {
            $query->where('client_id', $request->user()->client->id);
        } elseif ($request->user()->role === 'pharmacien') {
            $pharmacien = $request->user()->pharmacien;
            if ($pharmacien && $pharmacien->pharmacies->count() > 0) {
                $query->whereIn('pharmacie_id', $pharmacien->pharmacies->pluck('id'));
            }
        }

        return $query->get();
    }

    public function validees(Request $request)
    {
        $query = Ordonnance::with(['client', 'pharmacie'])
            ->where('statut', 'validee');

        if ($request->user()->role === 'client') {
            $query->where('client_id', $request->user()->client->id);
        } elseif ($request->user()->role === 'pharmacien') {
            $pharmacien = $request->user()->pharmacien;
            if ($pharmacien && $pharmacien->pharmacies->count() > 0) {
                $query->whereIn('pharmacie_id', $pharmacien->pharmacies->pluck('id'));
            }
        }

        return $query->get();
    }

    public function rejetees(Request $request)
    {
        $query = Ordonnance::with(['client', 'pharmacie'])
            ->where('statut', 'rejetee');

        if ($request->user()->role === 'client') {
            $query->where('client_id', $request->user()->client->id);
        } elseif ($request->user()->role === 'pharmacien') {
            $pharmacien = $request->user()->pharmacien;
            if ($pharmacien && $pharmacien->pharmacies->count() > 0) {
                $query->whereIn('pharmacie_id', $pharmacien->pharmacies->pluck('id'));
            }
        }

        return $query->get();
    }

    public function voirImage(Ordonnance $ordonnance)
    {
        // Déterminer le champ d'image à utiliser
        $imagePath = $ordonnance->photo_url ?? $ordonnance->image_ordonnance;
        
        if (!$imagePath) {
            return response()->json(['message' => 'Aucune image associée à cette ordonnance'], 404);
        }
        
        $filePath = storage_path('app/public/' . $imagePath);
        
        // Debug: log le chemin pour vérification
        \Log::info('Tentative d\'accès à l\'image:', [
            'ordonnance_id' => $ordonnance->id,
            'photo_url' => $ordonnance->photo_url,
            'image_ordonnance' => $ordonnance->image_ordonnance,
            'file_path' => $filePath,
            'file_exists' => file_exists($filePath)
        ]);
        
        if (!file_exists($filePath)) {
            return response()->json([
                'message' => 'Image non trouvée',
                'debug' => [
                    'path' => $filePath,
                    'ordonnance_id' => $ordonnance->id
                ]
            ], 404);
        }
        
        return response()->file($filePath);
    }

    private function envoyerNotificationPush($fcmToken, $data)
    {
        // Implémentation basique FCM (Firebase Cloud Messaging)
        $url = 'https://fcm.googleapis.com/fcm/send';
        $headers = [
            'Authorization: key=' . env('FCM_SERVER_KEY'),
            'Content-Type: application/json'
        ];
        
        $payload = [
            'to' => $fcmToken,
            'notification' => [
                'title' => $data['title'],
                'body' => $data['body']
            ],
            'data' => $data['data'] ?? []
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        
        $result = curl_exec($ch);
        curl_close($ch);
        
        return $result;
    }
}
