<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nom',
        'email',
        'password',
        'telephone',
        'adresse',
        'date_naissance',
        'role',
        'code_autorisation',
        'type_controle',
        'organisme'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_naissance' => 'date',
        ];
    }

    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function pharmacien()
    {
        return $this->hasOne(Pharmacien::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'client_id');
    }



    public function conseilsSante()
    {
        return $this->hasMany(ConseilSante::class, 'pharmacien_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function notificationsNonLues()
    {
        return $this->notifications()->where('lu', false);
    }
}
