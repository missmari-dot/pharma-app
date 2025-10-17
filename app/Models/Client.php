<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;
{
    protected $fillable = [
        'user_id',
        'adresse',
        'date_naissance'
    ];

    protected $casts = [
        'date_naissance' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
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