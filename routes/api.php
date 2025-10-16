<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PharmacieController;
use App\Http\Controllers\Api\OrdonnanceController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\ProduitController;
use App\Models\Pharmacie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Routes publiques
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/pharmacien/register', [\App\Http\Controllers\Auth\PharmacienRegisterController::class, 'register']);

// Routes publiques pharmacies
Route::get('/pharmacies', [PharmacieController::class, 'index']);
Route::get('/pharmacies/{pharmacie}', [PharmacieController::class, 'show']);
Route::post('/pharmacies/proches', [PharmacieController::class, 'pharmaciesProches']);
Route::get('/pharmacies/garde', [PharmacieController::class, 'pharmaciesDeGarde']);
Route::post('/geocode', [PharmacieController::class, 'geocodeAddress']);

// Routes publiques produits
Route::get('/produits', [ProduitController::class, 'index']);
Route::get('/produits/{produit}', [ProduitController::class, 'show']);
Route::get('/produits/{produit}/pharmacies', [ProduitController::class, 'pharmaciesDisponibles']);
Route::post('/produits/rechercher', [ProduitController::class, 'rechercher']);
Route::post('/recherche-geographique', [\App\Http\Controllers\Api\RechercheGeographiqueController::class, 'rechercherMedicaments']);
Route::post('/recherche-zone', [\App\Http\Controllers\Api\RechercheGeographiqueController::class, 'rechercherDansZone']);

// Routes publiques conseils santé
Route::get('/conseils-sante', [\App\Http\Controllers\Api\ConseilSanteController::class, 'index']);
Route::get('/conseils-sante/{conseilSante}', [\App\Http\Controllers\Api\ConseilSanteController::class, 'show']);

// Carte
Route::get('/map', [\App\Http\Controllers\MapController::class, 'index']);
Route::get('/map/pharmacies', [\App\Http\Controllers\MapController::class, 'pharmaciesAvecProduit']);
Route::get('/pharmacies/coordonnees', function() {
    return Pharmacie::select('id', 'nom_pharmacie', 'adresse_pharmacie', 'latitude', 'longitude', 'telephone_pharmacie', 'est_de_garde')
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->get();
});

// Corriger les coordonnées GPS
Route::post('/pharmacies/{pharmacie}/coordonnees', function(Request $request, Pharmacie $pharmacie) {
    $validated = $request->validate([
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180'
    ]);
    
    $pharmacie->update($validated);
    
    return response()->json([
        'message' => 'Coordonnées mises à jour',
        'pharmacie' => $pharmacie->only(['id', 'nom_pharmacie', 'latitude', 'longitude'])
    ]);
});

// Routes authentifiées
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard intelligent par rôle
    Route::get('/dashboard', [\App\Http\Controllers\Api\DashboardController::class, 'index']);

    // Dashboards spécialisés par rôle
    Route::get('/dashboard/client', [\App\Http\Controllers\Api\ClientDashboardController::class, 'dashboard']);
    Route::get('/dashboard/pharmacien', [\App\Http\Controllers\Api\PharmacienDashboardController::class, 'dashboard']);
    Route::get('/dashboard/autorite', [\App\Http\Controllers\Api\AutoriteSanteDashboardController::class, 'dashboard']);

    // Ordonnances
    Route::get('/ordonnances', [OrdonnanceController::class, 'index']);
    Route::post('/ordonnances', [OrdonnanceController::class, 'store']);
    Route::get('/ordonnances/{ordonnance}', [OrdonnanceController::class, 'show']);

    // Réservations
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
    Route::patch('/reservations/{reservation}/annuler', [ReservationController::class, 'annuler']);
});

// Routes pharmacien
Route::middleware(['auth:sanctum', 'role:pharmacien'])->group(function () {
    Route::post('/enregistrer-pharmacie', [\App\Http\Controllers\Api\PharmacienController::class, 'enregistrerPharmacie']);
    Route::post('/importer-stock', [\App\Http\Controllers\Api\StockImportController::class, 'importerStock']);
    Route::patch('/ordonnances/{ordonnance}/valider', [OrdonnanceController::class, 'valider']);
    Route::patch('/ordonnances/{ordonnance}/rejeter', [OrdonnanceController::class, 'rejeter']);
    Route::patch('/reservations/{reservation}/confirmer', [ReservationController::class, 'confirmerRetrait']);
    Route::patch('/reservations/{reservation}/valider', [ReservationController::class, 'validerAchat']);

    // Gestion pharmacie
    Route::get('/ma-pharmacie', function(Request $request) {
        $pharmacie = $request->user()->pharmacien->pharmacies()->first();
        return $pharmacie ? response()->json($pharmacie) : response()->json(['error' => 'Aucune pharmacie associée'], 404);
    });
    Route::patch('/ma-pharmacie', function(Request $request) {
        $pharmacie = $request->user()->pharmacien->pharmacies()->first();
        if (!$pharmacie) {
            return response()->json(['error' => 'Aucune pharmacie associée'], 404);
        }
        
        $validated = $request->validate([
            'nom_pharmacie' => 'sometimes|string|max:255',
            'adresse_pharmacie' => 'sometimes|string',
            'telephone_pharmacie' => 'sometimes|string|max:20',
            'heure_ouverture' => 'sometimes|date_format:H:i',
            'heure_fermeture' => 'sometimes|date_format:H:i',
            'est_de_garde' => 'sometimes|boolean'
        ]);
        
        $pharmacie->update($validated);
        return response()->json($pharmacie);
    });
    Route::post('/pharmacies', [PharmacieController::class, 'store']);
    Route::patch('/pharmacies/{pharmacie}', [PharmacieController::class, 'update']);
    Route::delete('/pharmacies/{pharmacie}', [PharmacieController::class, 'destroy']);

    // Debug auth
    Route::get('/debug-auth', function(Request $request) {
        return response()->json([
            'authenticated' => true,
            'user' => $request->user()->only(['id', 'nom', 'email', 'role']),
            'token_valid' => true
        ]);
    });

    // Gestion produits de ma pharmacie
    Route::get('/mes-produits', [\App\Http\Controllers\Api\PharmacienProduitController::class, 'index']);
    Route::post('/mes-produits', [\App\Http\Controllers\Api\PharmacienProduitController::class, 'store']);
    Route::patch('/mes-produits/{produit}', [\App\Http\Controllers\Api\PharmacienProduitController::class, 'update']);
    Route::delete('/mes-produits/{produit}', [\App\Http\Controllers\Api\PharmacienProduitController::class, 'destroy']);

    // Gestion des stocks
    Route::get('/pharmacies/{pharmacie}/stocks', [\App\Http\Controllers\Api\StockController::class, 'index']);
    Route::patch('/pharmacies/{pharmacie}/stocks/{produit}', [\App\Http\Controllers\Api\StockController::class, 'update']);
    Route::post('/pharmacies/{pharmacie}/stocks/{produit}/incrementer', [\App\Http\Controllers\Api\StockController::class, 'incrementer']);
    Route::post('/pharmacies/{pharmacie}/stocks/{produit}/decrementer', [\App\Http\Controllers\Api\StockController::class, 'decrementer']);

    // Conseils santé
    Route::post('/conseils-sante', [\App\Http\Controllers\Api\ConseilSanteController::class, 'store']);
    Route::patch('/conseils-sante/{conseilSante}', [\App\Http\Controllers\Api\ConseilSanteController::class, 'update']);
    Route::delete('/conseils-sante/{conseilSante}', [\App\Http\Controllers\Api\ConseilSanteController::class, 'destroy']);
});

// Routes Autorité de Santé
Route::middleware(['auth:sanctum', 'role:autorite_sante'])->prefix('autorite')->group(function () {
    Route::get('/demandes-pharmaciens', [\App\Http\Controllers\Admin\ValidationController::class, 'demandesPharmaciens']);
    Route::get('/demandes-pharmacies', [\App\Http\Controllers\Admin\ValidationController::class, 'demandesPharmacies']);
    Route::patch('/valider-pharmacien/{user}', [\App\Http\Controllers\Admin\ValidationController::class, 'validerPharmacien']);
    Route::patch('/valider-pharmacie/{pharmacie}', [\App\Http\Controllers\Admin\ValidationController::class, 'validerPharmacie']);
    Route::get('/rapport-dispensation', [\App\Http\Controllers\Api\AutoriteSanteController::class, 'rapportDispensation']);
    Route::get('/audit-pharmacies', [\App\Http\Controllers\Api\AutoriteSanteController::class, 'auditPharmacies']);
    Route::get('/statistiques-consommation', [\App\Http\Controllers\Api\AutoriteSanteController::class, 'statistiquesConsommation']);
    Route::get('/prescriptions-suspectes', [\App\Http\Controllers\Api\AutoriteSanteController::class, 'prescriptionsSuspectes']);
    Route::post('/controle-conformite', [\App\Http\Controllers\Api\AutoriteSanteController::class, 'controleConformite']);
});

// Routes Administrateur
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Api\AdminController::class, 'dashboard']);
    Route::patch('/utilisateurs/{user}', [\App\Http\Controllers\Api\AdminController::class, 'gererUtilisateur']);
    Route::get('/parametres', [\App\Http\Controllers\Api\AdminController::class, 'parametresSysteme']);
    Route::post('/parametres', [\App\Http\Controllers\Api\AdminController::class, 'parametresSysteme']);
    Route::get('/logs', [\App\Http\Controllers\Api\AdminController::class, 'logsSysteme']);
    Route::get('/statistiques', [\App\Http\Controllers\Api\AdminController::class, 'statistiquesUtilisation']);
});

// Routes Notifications
Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
    Route::get('/', function(\Illuminate\Http\Request $request) {
        $service = new \App\Services\NotificationService();
        return $service->notificationsUtilisateur($request->user());
    });
    Route::patch('/{id}/lire', function($id, \Illuminate\Http\Request $request) {
        $service = new \App\Services\NotificationService();
        return $service->marquerCommeLu($id, $request->user());
    });




});
//  Client
// Email: client@pharma.sn

// Password: password

// Rôle: client

// Nom: Fatou Sall

// Dashboard: /api/dashboard/client

// 💊 Pharmacien
// Email: pharmacien@pharma.sn

// Password: password

// Rôle: pharmacien

// Nom: Dr. Amadou Diallo

// Dashboard: /api/dashboard/pharmacien

// 👨‍💼 Administrateur
// Email: admin@pharma.sn

// Password: password

// Rôle: admin

// Nom: Admin Pharma

// Dashboard: /api/admin/dashboard

// 🏛️ Autorité de Santé
// Email: autorite@sante.sn

// Password: password

// Rôle: autorite_sante

// Nom: Dr. Amadou Diallo

// Code: AS-SN-2024-001

// Dashboard: /api/dashboard/autorite

// 🔬 Contrôle Pharmacovigilance
// Email: controle@sante.sn

// Password: password

// Rôle: autorite_sante

// Nom: Dr. Fatou Ndiaye

// Code: AS-SN-2024-002


