<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ordonnance extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'pharmacie_id',
        'photo_url',
        'statut',
        'date_envoi',
        'commentaire'
    ];

    protected $casts = [
        'date_envoi' => 'date'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function pharmacie()
    {
        return $this->belongsTo(Pharmacie::class);
    }

    public function reservation()
    {
        return $this->hasOne(Reservation::class);
    }

    public function lignesOrdonnance()
    {
        return $this->hasMany(LigneOrdonnance::class);
    }

    public function produits()
    {
        return $this->belongsToMany(Produit::class, 'ligne_ordonnances')
            ->withPivot('quantite_prescrite', 'dosage', 'instructions', 'statut', 'remarque_pharmacien')
            ->withTimestamps();
    }

    public function genererReservation()
    {
        if ($this->statut !== 'VALIDEE') {
            throw new \Exception('Ordonnance non validée');
        }

        return Reservation::create([
            'client_id' => $this->client_id,
            'pharmacie_id' => $this->pharmacie_id,
            'ordonnance_id' => $this->id,
            'date_reservation' => now(),
            'statut' => 'ACTIVE'
        ]);
    }
}
