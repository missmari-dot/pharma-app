<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $userData = [
            'nom' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'telephone' => '221771234567',
            'adresse' => 'Dakar, Sénégal',
            'date_naissance' => '1990-01-01',
            'role' => 'client'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'user' => [
                        'id',
                        'nom',
                        'email',
                        'role'
                    ],
                    'token'
                ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'client'
        ]);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'user' => [
                        'id',
                        'nom',
                        'email',
                        'role'
                    ],
                    'token'
                ]);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/logout');

        $response->assertStatus(200)
                ->assertJson(['message' => 'Déconnecté']);
    }

    public function test_authenticated_user_can_get_profile()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/me');

        $response->assertStatus(200)
                ->assertJson([
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email
                    ]
                ]);
    }
}
