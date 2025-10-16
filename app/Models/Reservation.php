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
        'montant_total'
    ];

    protected $casts = [
        'date_reservation' => 'datetime',
        'montant_total' => 'decimal:2'
    ];

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
}
