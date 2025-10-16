<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Client;
use App\Models\Pharmacien;
use Illuminate\Support\Facades\Hash;

class TestAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // Client de test
        $clientUser = User::create([
            'nom' => 'Aminata Diop',
            'email' => 'client@pharma.sn',
            'password' => Hash::make('password'),
            'telephone' => '221775551010',
            'adresse' => 'Plateau, Dakar',
            'date_naissance' => '1985-03-15',
            'role' => 'client',
            'email_verified_at' => now()
        ]);

        Client::create([
            'user_id' => $clientUser->id,
            'numero_carte_vitale' => 'CV-2024-001'
        ]);

        // Pharmacien de test
        $pharmacienUser = User::create([
            'nom' => 'Dr. Moussa Ba',
            'email' => 'pharmacien@pharma.sn',
            'password' => Hash::make('password'),
            'telephone' => '221775551020',
            'adresse' => 'Almadies, Dakar',
            'date_naissance' => '1978-07-22',
            'role' => 'pharmacien',
            'email_verified_at' => now()
        ]);

        Pharmacien::create([
            'user_id' => $pharmacienUser->id,
            'numero_licence' => 'PH-SN-2024-001',
            'specialite' => 'Pharmacie Clinique'
        ]);

        // Admin de test
        User::create([
            'nom' => 'Admin System',
            'email' => 'admin@pharma.sn',
            'password' => Hash::make('password'),
            'telephone' => '221775551030',
            'adresse' => 'Système',
            'date_naissance' => '1980-01-01',
            'role' => 'admin',
            'email_verified_at' => now()
        ]);
    }
}