<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LigneOrdonnance extends Model
{
    protected $fillable = [
        'ordonnance_id',
        'produit_id',
        'quantite_prescrite',
        'dosage',
        'instructions',
        'statut',
        'remarque_pharmacien'
    ];

    public function ordonnance()
    {
        return $this->belongsTo(Ordonnance::class);
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }
}