<?php

use App\Http\Controllers\Api\ProcessusReservationController;
use Illuminate\Support\Facades\Route;

// Processus unifié Client
Route::middleware('auth:sanctum')->group(function () {
    // 1. Client envoie ordonnance
    Route::post('/ordonnances', [ProcessusReservationController::class, 'envoyerOrdonnance']);
    
    // 3. Client crée réservation après validation
    Route::post('/ordonnances/{ordonnance}/reservation', [ProcessusReservationController::class, 'creerReservation']);
    
    // 4. Client confirme retrait
    Route::patch('/reservations/{reservation}/retrait', [ProcessusReservationController::class, 'confirmerRetrait']);
});

// Processus Pharmacien
Route::middleware(['auth:sanctum', 'role:pharmacien'])->group(function () {
    // 2. Pharmacien valide ordonnance
    Route::patch('/ordonnances/{ordonnance}/valider', [ProcessusReservationController::class, 'validerOrdonnance']);
});