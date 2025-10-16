<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LigneReservation extends Model
{
    protected $fillable = [
        'reservation_id',
        'produit_id',
        'quantite_reservee',
        'prix_unitaire'
    ];

    protected $casts = [
        'prix_unitaire' => 'decimal:2'
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }

    public function getSousTotal()
    {
        return $this->quantite_reservee * $this->prix_unitaire;
    }

    public function canReserve()
    {
        return $this->produit->stock >= $this->quantite_reservee;
    }
}
