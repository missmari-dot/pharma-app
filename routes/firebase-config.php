<?php

use Illuminate\Support\Facades\Route;

// Route pour fournir la config Firebase au frontend
Route::get('/firebase-config', function () {
    return response()->json([
        'apiKey' => config('firebase.api_key'),
        'authDomain' => config('firebase.auth_domain'),
        'projectId' => config('firebase.project_id'),
        'storageBucket' => config('firebase.storage_bucket'),
        'messagingSenderId' => config('firebase.fcm.sender_id'),
        'appId' => config('firebase.app_id'),
        'measurementId' => config('firebase.measurement_id'),
        'vapidKey' => config('firebase.fcm.vapid_key')
    ]);
});