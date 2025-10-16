<?php

namespace Tests\Feature;

use App\Models\Pharmacie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PharmacieTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_pharmacies_list()
    {
        Pharmacie::factory()->count(3)->create();

        $response = $this->getJson('/api/pharmacies');

        $response->assertStatus(200)
                ->assertJsonCount(3);
    }

    public function test_can_get_pharmacies_nearby()
    {
        $pharmacie = Pharmacie::factory()->create([
            'latitude' => 14.6937,
            'longitude' => -17.4441
        ]);

        $response = $this->postJson('/api/pharmacies/proches', [
            'latitude' => 14.6937,
            'longitude' => -17.4441,
            'radius' => 5
        ]);

        $response->assertStatus(200)
                ->assertJsonCount(1);
    }

    public function test_can_get_pharmacies_de_garde()
    {
        Pharmacie::factory()->create(['est_de_garde' => true]);
        Pharmacie::factory()->create(['est_de_garde' => false]);

        $response = $this->getJson('/api/pharmacies/garde');

        $response->assertStatus(200)
                ->assertJsonCount(1);
    }

    public function test_pharmacien_can_create_pharmacie()
    {
        $user = User::factory()->create(['role' => 'pharmacien']);
        $token = $user->createToken('test-token')->plainTextToken;

        $pharmacieData = [
            'nom_pharmacie' => 'Pharmacie Test',
            'adresse_pharmacie' => 'Test Address',
            'telephone_pharmacie' => '221771234567',
            'heure_ouverture' => '08:00',
            'heure_fermeture' => '20:00',
            'latitude' => 14.6937,
            'longitude' => -17.4441
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/pharmacies', $pharmacieData);

        $response->assertStatus(201)
                ->assertJson(['nom_pharmacie' => 'Pharmacie Test']);

        $this->assertDatabaseHas('pharmacies', [
            'nom_pharmacie' => 'Pharmacie Test'
        ]);
    }

    public function test_non_pharmacien_cannot_create_pharmacie()
    {
        $user = User::factory()->create(['role' => 'client']);
        $token = $user->createToken('test-token')->plainTextToken;

        $pharmacieData = [
            'nom_pharmacie' => 'Pharmacie Test',
            'adresse_pharmacie' => 'Test Address'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/pharmacies', $pharmacieData);

        $response->assertStatus(403);
    }
}
