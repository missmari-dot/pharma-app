<?php

namespace App\Policies;

use App\Models\Ordonnance;
use App\Models\User;

class OrdonnancePolicy
{
    public function view(User $user, Ordonnance $ordonnance): bool
    {
        if ($user->id === $ordonnance->client_id) {
            return true;
        }
        
        if ($user->role === 'pharmacien' && $user->pharmacien) {
            return $user->pharmacien->pharmacies->contains('id', $ordonnance->pharmacie_id);
        }
        
        return false;
    }

    public function update(User $user, Ordonnance $ordonnance): bool
    {
        if ($user->role === 'pharmacien' && $user->pharmacien) {
            return $user->pharmacien->pharmacies->contains('id', $ordonnance->pharmacie_id);
        }
        
        return false;
    }

    public function delete(User $user, Ordonnance $ordonnance): bool
    {
        return $user->id === $ordonnance->client_id;
    }
}