<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProduitParapharmacie extends Model
{
    protected $fillable = [
        'produit_id',
        'marque',
        'categorie_parapharmacie'
    ];

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }
}