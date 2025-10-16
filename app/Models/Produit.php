<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produit extends Model
{
    protected $fillable = [
        'nom_produit',
        'description',
        'prix',
        'image',
        'categorie',
        'necessite_ordonnance',
        'stock'
    ];

    protected $casts = [
        'prix' => 'decimal:2',
        'necessite_ordonnance' => 'boolean'
    ];

    public function pharmacies()
    {
        return $this->belongsToMany(Pharmacie::class)->withPivot('quantite_disponible')->withTimestamps();
    }

    public function lignesReservation()
    {
        return $this->hasMany(LigneReservation::class);
    }

    public function medicament()
    {
        return $this->hasOne(Medicament::class);
    }

    public function produitParapharmacie()
    {
        return $this->hasOne(ProduitParapharmacie::class);
    }

    public function isMedicament()
    {
        return $this->categorie === 'Médicament';
    }

    public function isParapharmacie()
    {
        return $this->categorie === 'Parapharmacie';
    }

    public function isInStock()
    {
        return $this->stock > 0;
    }

    public function decrementStock($quantity = 1)
    {
        if ($this->stock >= $quantity) {
            $this->decrement('stock', $quantity);
            return true;
        }
        return false;
    }
}
