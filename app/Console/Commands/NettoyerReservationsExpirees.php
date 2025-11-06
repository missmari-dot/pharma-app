<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class NettoyerReservationsExpirees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:nettoyer-expirees';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nettoie les réservations expirées et libère le stock';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $reservationsExpirees = \App\Models\Reservation::where('statut', 'ACTIVE')
            ->where('date_expiration', '<', now())
            ->get();

        foreach ($reservationsExpirees as $reservation) {
            $reservation->update(['statut' => 'EXPIREE']);
        }

        $this->info('Nettoyé ' . $reservationsExpirees->count() . ' réservations expirées');
    }
}
