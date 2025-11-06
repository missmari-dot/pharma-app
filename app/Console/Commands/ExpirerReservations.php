<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Illuminate\Console\Command;

class ExpirerReservations extends Command
{
    protected $signature = 'reservations:expirer';
    protected $description = 'Marquer les réservations expirées après 24h';

    public function handle()
    {
        $reservationsExpirees = Reservation::where('statut', 'en_attente')
            ->where('date_reservation', '<', now()->subHours(24))
            ->get();

        $count = 0;
        foreach ($reservationsExpirees as $reservation) {
            if ($reservation->marquerCommeExpiree()) {
                $count++;
            }
        }

        $this->info("$count réservations expirées traitées.");
        return 0;
    }
}