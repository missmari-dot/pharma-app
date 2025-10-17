<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pharmacie extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_pharmacie',
        'adresse_pharmacie', 
        'telephone_pharmacie',
        'heure_ouverture',
        'heure_fermeture',
        'est_de_garde',
        'latitude',
        'longitude',
        'pharmacien_id'
    ];

    protected $casts = [
        'heure_ouverture' => 'datetime:H:i',
        'heure_fermeture' => 'datetime:H:i',
        'est_de_garde' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8'
    ];

    public function pharmacien()
    {
        return $this->belongsTo(Pharmacien::class, 'pharmacien_id');
    }

    public function produits()
    {
        return $this->belongsToMany(Produit::class, 'pharmacie_produit')
            ->withPivot('quantite_disponible')
            ->withTimestamps();
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function ordonnances()
    {
        return $this->hasMany(Ordonnance::class);
    }
}
