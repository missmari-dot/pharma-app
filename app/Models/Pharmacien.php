<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pharmacien extends Model
{
    use HasFactory;
{
    protected $fillable = [
        'user_id',
        'pharmacies_associees'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pharmacies()
    {
        return $this->hasMany(Pharmacie::class);
    }

    public function conseilsSante()
    {
        return $this->hasMany(ConseilSante::class);
    }

    public function syncPharmaciesAssociees()
    {
        $pharmaciesIds = $this->pharmacies->pluck('id')->toArray();
        $this->update([
            'pharmacies_associees' => implode(',', $pharmaciesIds)
        ]);
    }
}