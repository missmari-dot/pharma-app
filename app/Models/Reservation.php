<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'client_id',
        'pharmacie_id',
        'ordonnance_id',
        'date_reservation',
        'statut',
        'montant_total',
        'code_retrait'
    ];

    protected $casts = [
        'date_reservation' => 'datetime',
        'montant_total' => 'decimal:2'
    ];

    protected static function booted()
    {
        static::retrieved(function ($reservation) {
            if ($reservation->estExpiree()) {
                $reservation->marquerCommeExpiree();
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function pharmacie()
    {
        return $this->belongsTo(Pharmacie::class);
    }

    public function ordonnance()
    {
        return $this->belongsTo(Ordonnance::class);
    }

    public function lignesReservation()
    {
        return $this->hasMany(LigneReservation::class);
    }

    public function calculerTotal()
    {
        return $this->lignesReservation->sum(function ($ligne) {
            return $ligne->quantite_reservee * $ligne->prix_unitaire;
        });
    }

    public static function genererCodeRetrait()
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
        } while (self::where('code_retrait', $code)->exists());
        
        return $code;
    }

    public function estExpiree()
    {
        return $this->statut === 'en_attente' && 
               $this->date_reservation->addHours(24)->isPast();
    }

    public function marquerCommeExpiree()
    {
        if ($this->estExpiree()) {
            $this->update(['statut' => 'expire']);
            
            // Libérer le stock réservé
            foreach ($this->lignesReservation as $ligne) {
                $this->pharmacie->produits()->updateExistingPivot($ligne->produit_id, [
                    'quantite_disponible' => \DB::raw("quantite_disponible + {$ligne->quantite_reservee}")
                ]);
            }
            
            return true;
        }
        
        return false;
    }
}
