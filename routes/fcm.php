<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\FirebaseService;

// Routes pour Firebase Cloud Messaging
Route::middleware('auth:sanctum')->group(function () {
    
    // Enregistrer le token FCM de l'utilisateur
    Route::post('/fcm/register-token', function (Request $request) {
        $request->validate([
            'token' => 'required|string'
        ]);
        
        $user = $request->user();
        $user->fcm_token = $request->token;
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Token FCM enregistré'
        ]);
    });
    
    // Test notification push
    Route::post('/fcm/test-notification', function (Request $request) {
        $user = $request->user();
        
        if (!$user->fcm_token) {
            return response()->json([
                'success' => false,
                'message' => 'Token FCM non enregistré'
            ], 400);
        }
        
        $firebaseService = new FirebaseService();
        $success = $firebaseService->envoyerNotificationPush(
            $user->fcm_token,
            'Test PharmaMobile',
            'Notification push de test',
            ['type' => 'test']
        );
        
        return response()->json([
            'success' => $success,
            'message' => $success ? 'Notification envoyée' : 'Erreur envoi'
        ]);
    });
    
    // Supprimer le token FCM
    Route::delete('/fcm/unregister-token', function (Request $request) {
        $user = $request->user();
        $user->fcm_token = null;
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Token FCM supprimé'
        ]);
    });
});