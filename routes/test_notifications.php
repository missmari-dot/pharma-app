<?php

use App\Services\NotificationService;
use App\Services\NotificationPersonnaliseeService;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// Routes de test pour les notifications personnalisées
Route::middleware('auth:sanctum')->prefix('test-notifications')->group(function () {
    
    // Tester notification personnalisée
    Route::post('/personnalisee', function(\Illuminate\Http\Request $request) {
        $service = new NotificationPersonnaliseeService();
        
        $service->notifierPromotionPersonnalisee(
            $request->user()->id,
            "Offre spéciale : 20% de réduction sur vos médicaments habituels !",
            ['paracetamol', 'ibuprofene']
        );
        
        return response()->json(['message' => 'Notification personnalisée envoyée']);
    });
    
    // Tester rappel médicament
    Route::post('/rappel-medicament', function(\Illuminate\Http\Request $request) {
        $service = new NotificationPersonnaliseeService();
        
        $service->notifierRappelMedicament(
            $request->user()->id,
            'Paracétamol 500mg',
            '14:00'
        );
        
        return response()->json(['message' => 'Rappel médicament envoyé']);
    });
    
    // Tester conseil santé personnalisé
    Route::post('/conseil-sante', function(\Illuminate\Http\Request $request) {
        $service = new NotificationPersonnaliseeService();
        
        $service->notifierConseilSantePersonnalise(
            $request->user()->id,
            "Basé sur vos achats récents, pensez à boire beaucoup d'eau avec vos médicaments."
        );
        
        return response()->json(['message' => 'Conseil santé personnalisé envoyé']);
    });
});