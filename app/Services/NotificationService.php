<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use App\Models\Ordonnance;
use App\Models\Reservation;
use App\Services\FirebaseService;

class NotificationService
{
    public function notifierOrdonnanceRecue(Ordonnance $ordonnance)
    {
        $pharmacien = $ordonnance->pharmacie->pharmacien->user;

        // Notification push (à implémenter avec FCM)
        $this->envoyerNotification($pharmacien, [
            'titre' => 'Nouvelle ordonnance reçue',
            'message' => "Ordonnance de {$ordonnance->client->nom} en attente de validation",
            'type' => 'ordonnance_recue',
            'data' => ['ordonnance_id' => $ordonnance->id]
        ]);
    }

    public function notifierOrdonnanceValidee(Ordonnance $ordonnance)
    {
        $client = $ordonnance->client;

        $this->envoyerNotification($client->user, [
            'titre' => 'Ordonnance validée',
            'message' => 'Votre ordonnance a été validée. Vous pouvez maintenant réserver vos médicaments.',
            'type' => 'ordonnance_validee',
            'data' => ['ordonnance_id' => $ordonnance->id]
        ]);

        // Envoyer SMS si numéro disponible
        if ($client->user->telephone) {
            $smsService = new SmsService();
            $smsService->smsOrdonnanceValidee(
                $client->user->telephone,
                $ordonnance->pharmacie->nom_pharmacie
            );
        }
    }

    public function notifierOrdonnanceRejetee(Ordonnance $ordonnance)
    {
        $client = $ordonnance->client;

        $this->envoyerNotification($client->user, [
            'titre' => 'Ordonnance rejetée',
            'message' => 'Votre ordonnance a été rejetée. Veuillez contacter la pharmacie pour plus d\'informations.',
            'type' => 'ordonnance_rejetee',
            'data' => ['ordonnance_id' => $ordonnance->id]
        ]);

        // Envoyer SMS si numéro disponible
        if ($client->user->telephone) {
            $smsService = new SmsService();
            $smsService->smsOrdonnanceRejetee(
                $client->user->telephone,
                $ordonnance->pharmacie->nom_pharmacie,
                $ordonnance->commentaire
            );
        }
    }

    public function notifierReservationPrete(Reservation $reservation)
    {
        $client = $reservation->client;

        $this->envoyerNotification($client->user, [
            'titre' => 'Réservation prête',
            'message' => 'Vos médicaments sont prêts. Vous pouvez venir les récupérer.',
            'type' => 'reservation_prete',
            'data' => ['reservation_id' => $reservation->id]
        ]);

        // Envoyer SMS si numéro disponible
        if ($client->user->telephone) {
            $smsService = new SmsService();
            $smsService->smsReservationPrete(
                $client->user->telephone,
                $reservation->code_retrait,
                $reservation->pharmacie->nom_pharmacie
            );
        }
    }

    public function alerteStockFaible($produit, $pharmacie)
    {
        $pharmacien = $pharmacie->pharmacien->user;

        $this->envoyerNotification($pharmacien, [
            'titre' => 'Stock faible',
            'message' => "Stock faible pour {$produit->nom_produit}",
            'type' => 'stock_faible',
            'data' => ['produit_id' => $produit->id]
        ]);
    }

    private function envoyerNotification(User $user, array $notification)
    {
        // Créer notification personnalisée pour cet utilisateur uniquement
        Notification::create([
            'user_id' => $user->id,
            'titre' => $notification['titre'],
            'message' => $notification['message'],
            'type' => $notification['type'],
            'data' => $notification['data'] ?? [],
            'lu' => false
        ]);

        // Envoyer notification push Firebase si token disponible
        if ($user->fcm_token) {
            $firebaseService = new FirebaseService();
            $firebaseService->envoyerNotificationPush(
                $user->fcm_token,
                $notification['titre'],
                $notification['message'],
                $notification['data'] ?? []
            );
        }

        \Log::info('Notification personnalisée envoyée', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'type' => $notification['type'],
            'has_fcm_token' => !empty($user->fcm_token)
        ]);
    }

    public function notificationsUtilisateur(User $user)
    {
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'notifications' => $notifications,
            'total' => $notifications->count(),
            'non_lues' => $user->notificationsNonLues()->count()
        ]);
    }

    public function marquerCommeLu($notificationId, User $user)
    {
        $notification = $user->notifications()->find($notificationId);
        
        if (!$notification) {
            return response()->json(['message' => 'Notification non trouvée'], 404);
        }

        $notification->marquerCommeLue();

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue'
        ]);
    }

    public function toutMarquerCommeLu(User $user)
    {
        $count = $user->notificationsNonLues()->count();
        $user->notificationsNonLues()->update(['lu' => true]);

        return response()->json([
            'success' => true,
            'message' => "$count notifications marquées comme lues",
            'count' => $count
        ]);
    }

    public function notificationsNonLues(User $user)
    {
        $notifications = $user->notificationsNonLues()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'notifications' => $notifications,
            'count' => $notifications->count()
        ]);
    }

    public function compterNonLues(User $user)
    {
        return $user->notificationsNonLues()->count();
    }
}
