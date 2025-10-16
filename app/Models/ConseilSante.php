<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConseilSante extends Model
{
    protected $fillable = [
        'titre',
        'contenu',
        'categorie',
        'date_publication',
        'pharmacien_id'
    ];

    protected $casts = [
        'date_publication' => 'date'
    ];

    public function pharmacien()
    {
        return $this->belongsTo(Pharmacien::class, 'pharmacien_id');
    }
}
