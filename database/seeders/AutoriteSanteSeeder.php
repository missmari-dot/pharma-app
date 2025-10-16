<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AutoriteSante;
use Illuminate\Support\Facades\Hash;

class AutoriteSanteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AutoriteSante::create([
            'nom' => 'Dr. Amadou Diallo',
            'email' => 'autorite@sante.sn',
            'password' => Hash::make('password'),
            'telephone' => '221775551001',
            'adresse' => 'Ministère de la Santé, Dakar',
            'date_naissance' => '1975-05-15',
            'role' => 'autorite_sante',
            'code_autorisation' => 'AS-SN-2024-001',
            'type_controle' => 'GENERAL',
            'organisme' => 'Autorité de Santé du Sénégal',
            'email_verified_at' => now()
        ]);

        AutoriteSante::create([
            'nom' => 'Dr. Fatou Ndiaye',
            'email' => 'controle@sante.sn',
            'password' => Hash::make('password'),
            'telephone' => '221775551002',
            'adresse' => 'Direction de la Pharmacie, Dakar',
            'date_naissance' => '1980-08-22',
            'role' => 'autorite_sante',
            'code_autorisation' => 'AS-SN-2024-002',
            'type_controle' => 'PHARMACOVIGILANCE',
            'organisme' => 'Direction de la Pharmacie et du Médicament',
            'email_verified_at' => now()
        ]);
    }
}
