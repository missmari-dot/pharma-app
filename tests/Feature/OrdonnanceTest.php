<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Ordonnance;
use App\Models\Pharmacie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrdonnanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_send_ordonnance()
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'client']);
        $client = Client::factory()->create(['user_id' => $user->id]);
        $pharmacie = Pharmacie::factory()->create(['statut_validation' => 'approved']);

        $token = $user->createToken('test-token')->plainTextToken;

        $file = UploadedFile::fake()->create('ordonnance.jpg', 100, 'image/jpeg');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/ordonnances', [
            'pharmacie_id' => $pharmacie->id,
            'photo_ordonnance' => $file,
            'commentaire' => 'Test comment'
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'ordonnance' => [
                        'id',
                        'statut',
                        'photo_url'
                    ]
                ]);

        $this->assertDatabaseHas('ordonnances', [
            'client_id' => $client->id,
            'pharmacie_id' => $pharmacie->id,
            'statut' => 'envoyee'
        ]);
    }

    public function test_pharmacien_can_validate_ordonnance()
    {
        $pharmacienUser = User::factory()->create(['role' => 'pharmacien']);
        $pharmacien = \App\Models\Pharmacien::factory()->create(['user_id' => $pharmacienUser->id]);
        $pharmacie = Pharmacie::factory()->create(['pharmacien_id' => $pharmacien->id]);
        $ordonnance = Ordonnance::factory()->create([
            'pharmacie_id' => $pharmacie->id,
            'statut' => 'envoyee'
        ]);

        $token = $pharmacienUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->patchJson("/api/ordonnances/{$ordonnance->id}/valider", [
            'remarque_pharmacien' => 'Ordonnance validée'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('ordonnances', [
            'id' => $ordonnance->id,
            'statut' => 'validee'
        ]);
    }

    public function test_pharmacien_can_reject_ordonnance()
    {
        $pharmacienUser = User::factory()->create(['role' => 'pharmacien']);
        $pharmacien = \App\Models\Pharmacien::factory()->create(['user_id' => $pharmacienUser->id]);
        $pharmacie = Pharmacie::factory()->create(['pharmacien_id' => $pharmacien->id]);
        $ordonnance = Ordonnance::factory()->create([
            'pharmacie_id' => $pharmacie->id,
            'statut' => 'envoyee'
        ]);

        $token = $pharmacienUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->patchJson("/api/ordonnances/{$ordonnance->id}/rejeter", [
            'commentaire' => 'Ordonnance illisible'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('ordonnances', [
            'id' => $ordonnance->id,
            'statut' => 'rejetee'
        ]);
    }

    public function test_client_can_view_own_ordonnances()
    {
        $user = User::factory()->create(['role' => 'client']);
        $client = Client::factory()->create(['user_id' => $user->id]);
        $ordonnance = Ordonnance::factory()->create(['client_id' => $client->id]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/ordonnances');

        $response->assertStatus(200)
                ->assertJsonCount(1);
    }
}
