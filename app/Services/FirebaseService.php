<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class FirebaseService
{
    private $projectId;
    private $credentials;
    private $vapidKey;
    
    public function __construct()
    {
        $this->projectId = config('firebase.project_id');
        $this->vapidKey = config('firebase.fcm.vapid_key');
        $this->credentials = json_decode(file_get_contents(config_path('firebase-credentials.json')), true);
    }
    
    private function getAccessToken()
    {
        $now = time();
        $payload = [
            'iss' => $this->credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600
        ];
        
        $jwt = JWT::encode($payload, $this->credentials['private_key'], 'RS256');
        
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]);
        
        if ($response->successful()) {
            return $response->json()['access_token'];
        }
        
        throw new \Exception('Impossible d\'obtenir le token d\'accès Firebase');
    }
    
    public function envoyerNotificationPush($token, $titre, $message, $data = [])
    {
        try {
            $accessToken = $this->getAccessToken();
            
            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $titre,
                        'body' => $message
                    ],
                    'data' => array_map('strval', $data),
                    'webpush' => [
                        'headers' => [
                            'Urgency' => 'high'
                        ],
                        'notification' => [
                            'title' => $titre,
                            'body' => $message,
                            'icon' => '/assets/icons/icon-192x192.png',
                            'badge' => '/assets/icons/badge-72x72.png'
                        ]
                    ]
                ]
            ];
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->post("https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send", $payload);
            
            if ($response->successful()) {
                Log::info('Notification push envoyée (HTTP v1)', ['token' => substr($token, 0, 20) . '...']);
                return true;
            } else {
                Log::error('Erreur FCM HTTP v1', ['response' => $response->body()]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception FCM HTTP v1', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    public function envoyerNotificationMultiple($tokens, $titre, $message, $data = [])
    {
        if (!$this->serverKey || empty($tokens)) {
            return false;
        }
        
        $payload = [
            'registration_ids' => $tokens,
            'notification' => [
                'title' => $titre,
                'body' => $message,
                'icon' => '/assets/icons/icon-192x192.png'
            ],
            'data' => $data
        ];
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json'
            ])->post('https://fcm.googleapis.com/fcm/send', $payload);
            
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Exception FCM multiple', ['error' => $e->getMessage()]);
            return false;
        }
    }
}