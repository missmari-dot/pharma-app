<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Client;
use App\Models\Notification;
use Illuminate\Support\Facades\Hash;

class NotificationTestSeeder extends Seeder
{
    public function run()
    {
        // Créer 2 clients de test
        $client1 = User::create([
            'nom' => 'Client Test 1',
            'email' => 'client1@test.com',
            'password' => Hash::make('password'),
            'telephone' => '0123456789',
            'adresse' => 'Adresse test 1',
            'date_naissance' => '1990-01-01',
            'role' => 'client'
        ]);

        Client::create(['user_id' => $client1->id]);

        $client2 = User::create([
            'nom' => 'Client Test 2', 
            'email' => 'client2@test.com',
            'password' => Hash::make('password'),
            'telephone' => '0987654321',
            'adresse' => 'Adresse test 2',
            'date_naissance' => '1985-05-15',
            'role' => 'client'
        ]);

        Client::create(['user_id' => $client2->id]);

        // Créer des notifications personnalisées pour chaque client
        Notification::create([
            'user_id' => $client1->id,
            'titre' => 'Notification personnalisée Client 1',
            'message' => 'Ceci est une notification uniquement pour le client 1',
            'type' => 'test_personnalise',
            'data' => ['client' => 'client1']
        ]);

        Notification::create([
            'user_id' => $client2->id,
            'titre' => 'Notification personnalisée Client 2',
            'message' => 'Ceci est une notification uniquement pour le client 2',
            'type' => 'test_personnalise', 
            'data' => ['client' => 'client2']
        ]);

        echo "✅ Utilisateurs de test créés:\n";
        echo "   - client1@test.com / password\n";
        echo "   - client2@test.com / password\n";
        echo "✅ Notifications personnalisées créées pour chaque client\n";
    }
}