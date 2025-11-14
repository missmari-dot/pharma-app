<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'titre',
        'message',
        'type',
        'data',
        'lu'
    ];

    protected $casts = [
        'data' => 'array',
        'lu' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeNonLues($query)
    {
        return $query->where('lu', false);
    }

    public function scopePourUtilisateur($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function marquerCommeLue()
    {
        $this->update(['lu' => true]);
    }
}