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
        'pharmacien_id',
        'statut_validation',
        'numero_agrement',
        'documents_justificatifs'
    ];

    protected $casts = [
        'heure_ouverture' => 'datetime:H:i',
        'heure_fermeture' => 'datetime:H:i',
        'est_de_garde' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'date_sanction' => 'datetime',
        'date_fin_sanction' => 'datetime'
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

    public function sanctionneurUser()
    {
        return $this->belongsTo(User::class, 'sanctionnee_par');
    }

    public function estActive()
    {
        return $this->statut_activite === 'active';
    }

    public function estSuspendue()
    {
        if ($this->statut_activite === 'suspendue' && $this->date_fin_sanction) {
            if (now()->isAfter($this->date_fin_sanction)) {
                $this->update(['statut_activite' => 'active', 'date_fin_sanction' => null]);
                return false;
            }
            return true;
        }
        return false;
    }

    public function estBloquee()
    {
        return $this->statut_activite === 'bloquee';
    }
}
