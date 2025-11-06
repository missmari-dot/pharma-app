<?php

namespace App\Services;

use App\Models\User;
use App\Models\Ordonnance;
use App\Models\Reservation;

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
        // Sauvegarde en base pour historique
        \DB::table('notifications')->insert([
            'user_id' => $user->id,
            'titre' => $notification['titre'],
            'message' => $notification['message'],
            'type' => $notification['type'],
            'data' => json_encode($notification['data'] ?? []),
            'lu' => false,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Log pour debug
        \Log::info('Notification envoyée', [
            'user' => $user->email,
            'type' => $notification['type'],
            'message' => $notification['message']
        ]);
    }

    public function notificationsUtilisateur(User $user)
    {
        return \DB::table('notifications')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
    }

    public function marquerCommeLu($notificationId, User $user)
    {
        return \DB::table('notifications')
            ->where('id', $notificationId)
            ->where('user_id', $user->id)
            ->update(['lu' => true]);
    }
}
