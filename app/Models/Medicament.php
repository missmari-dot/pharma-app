<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medicament extends Model
{
    protected $fillable = [
        'produit_id',
        'posologie',
        'necessite_ordonnance',
        'autorite_id'
    ];

    protected $casts = [
        'necessite_ordonnance' => 'boolean'
    ];

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }

    public function autorite()
    {
        return $this->belongsTo(AutoriteSante::class, 'autorite_id');
    }

    public function verifierPrescription()
    {
        return $this->autorite?->verifierCadreLegal($this) ?? true;
    }
}