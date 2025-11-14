<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PharmacieController;
use App\Http\Controllers\Api\OrdonnanceController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\ProduitController;
use App\Http\Controllers\Api\ConseilSanteController;
use App\Http\Controllers\Api\RechercheGeographiqueController;
use App\Http\Controllers\Api\StockImportController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\PharmacienController;
use App\Http\Controllers\Api\PharmacienProduitController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ClientDashboardController;
use App\Http\Controllers\Api\PharmacienDashboardController;
use App\Http\Controllers\Api\AutoriteSanteDashboardController;
use App\Http\Controllers\Api\AutoriteSanteController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Auth\PharmacienRegisterController;
use App\Http\Controllers\Admin\ValidationController;

use App\Http\Controllers\MapController;
use App\Models\Pharmacie;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| API Routes - PharmaMobile
|--------------------------------------------------------------------------
*/

// ============================================
// ROUTES PUBLIQUES (Sans authentification)
// ============================================

// Authentication (routes directes)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/pharmacien/register', [PharmacienRegisterController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Authentication (avec préfixe auth)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/pharmacien/register', [PharmacienRegisterController::class, 'register']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Pharmacies
Route::prefix('pharmacies')->group(function () {
    Route::get('/', [PharmacieController::class, 'index']);
    Route::get('/de-garde', [PharmacieController::class, 'pharmaciesDeGarde']);
    Route::post('/proches', [PharmacieController::class, 'pharmaciesProches']);
    Route::get('/coordonnees', function() {
        return Pharmacie::select('id', 'nom_pharmacie', 'adresse_pharmacie', 'latitude', 'longitude', 'telephone_pharmacie', 'est_de_garde', 'heure_ouverture', 'heure_fermeture')
            ->where('statut_validation', 'approved')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();
    });
    Route::get('/{pharmacie}', [PharmacieController::class, 'show']);
});

// Produits
Route::prefix('produits')->group(function () {
    Route::get('/', [ProduitController::class, 'index']);
    Route::get('/rechercher', [ProduitController::class, 'rechercher']);
    Route::get('/{produit}', [ProduitController::class, 'show']);
    Route::get('/{produit}/pharmacies', [ProduitController::class, 'pharmaciesDisponibles']);
});

// Recherche géographique
Route::get('/recherche-geographique', [RechercheGeographiqueController::class, 'rechercherMedicaments']);
Route::get('/recherche-zone', [RechercheGeographiqueController::class, 'rechercherDansZone']);

// Conseils santé
Route::prefix('conseils-sante')->group(function () {
    Route::get('/', [ConseilSanteController::class, 'index']);
    Route::get('/{conseilSante}', [ConseilSanteController::class, 'show']);
});

// Carte interactive
Route::prefix('map')->group(function () {
    Route::post('/pharmacies-proches', [\App\Http\Controllers\Api\MapController::class, 'pharmaciesProches']);
    Route::post('/itineraire', [\App\Http\Controllers\Api\MapController::class, 'itineraire']);
});

// Utilitaires
Route::post('/geocode', [PharmacieController::class, 'geocodeAddress']);



// Test SMS (pour démonstration)
Route::post('/test-sms', function(\Illuminate\Http\Request $request) {
    $validated = $request->validate([
        'telephone' => 'required|string',
        'message' => 'required|string|max:160'
    ]);
    
    $smsService = new \App\Services\SmsService();
    $result = $smsService->envoyerSms($validated['telephone'], $validated['message']);
    
    return response()->json($result);
});

// Inclure les routes de test des notifications
require __DIR__ . '/test_notifications.php';

// ============================================
// ROUTES AUTHENTIFIÉES (Tous rôles)
// ============================================

Route::middleware('auth:sanctum')->group(function () {

    // Profile & Auth
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/validate', [AuthController::class, 'validateToken']);

    // Dashboards intelligents par rôle
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/client', [ClientDashboardController::class, 'dashboard']);
    Route::get('/dashboard/pharmacien', [PharmacienDashboardController::class, 'dashboard']);
    Route::get('/dashboard/autorite', [AutoriteSanteDashboardController::class, 'dashboard']);

    // Ordonnances
    Route::prefix('ordonnances')->group(function () {
        Route::get('/', [OrdonnanceController::class, 'index']);
        Route::get('/en-attente', [OrdonnanceController::class, 'enAttente']);
        Route::get('/validees', [OrdonnanceController::class, 'validees']);
        Route::get('/rejetees', [OrdonnanceController::class, 'rejetees']);

        Route::post('/', [OrdonnanceController::class, 'store']);
        Route::post('/envoyer-sans-medicaments', [OrdonnanceController::class, 'envoyerSansMedicaments']);
        Route::post('/upload', [OrdonnanceController::class, 'uploadImage']);
        Route::get('/{ordonnance}', [OrdonnanceController::class, 'show']);
        Route::delete('/{ordonnance}', [OrdonnanceController::class, 'destroy']);
    });

    // Réservations
    Route::prefix('reservations')->group(function () {
        Route::get('/', [ReservationController::class, 'index']);
        Route::post('/', [ReservationController::class, 'store']);
        Route::get('/{reservation}', [ReservationController::class, 'show']);
        Route::patch('/{reservation}/annuler', [ReservationController::class, 'annuler']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
        Route::get('/non-lues', [\App\Http\Controllers\Api\NotificationController::class, 'nonLues']);
        Route::get('/compter', [\App\Http\Controllers\Api\NotificationController::class, 'compter']);
        Route::patch('/{id}/lire', [\App\Http\Controllers\Api\NotificationController::class, 'marquerCommeLu'])
            ->middleware(\App\Http\Middleware\NotificationOwnership::class);
        Route::patch('/tout-lire', [\App\Http\Controllers\Api\NotificationController::class, 'toutMarquerCommeLu']);
    });
});

// ============================================
// ROUTES PHARMACIEN
// ============================================

Route::middleware(['auth:sanctum', 'role:pharmacien'])->prefix('pharmacien')->group(function () {

    // Enregistrement pharmacie
    Route::post('/enregistrer-pharmacie', [PharmacienController::class, 'enregistrerPharmacie']);
    
    // Debug temporaire (sans auth)
    Route::post('/debug-enregistrer-pharmacie', function(Request $request) {
        return response()->json([
            'all_data' => $request->all(),
            'files' => $request->allFiles(),
            'content_type' => $request->header('Content-Type'),
            'has_documents_file' => $request->hasFile('documents_justificatifs'),
            'validation_errors' => []
        ]);
    })->withoutMiddleware(['auth:sanctum', 'role:pharmacien']);
    

    Route::get('/ma-pharmacie', function(Request $request) {
        $pharmacie = $request->user()->pharmacien?->pharmacies()->first();
        return $pharmacie
            ? response()->json($pharmacie)
            : response()->json(['error' => 'Aucune pharmacie associée'], 404);
    });
});

// Route ma-pharmacie accessible directement
Route::middleware(['auth:sanctum', 'role:pharmacien'])->get('/ma-pharmacie', function(Request $request) {
    $pharmacie = $request->user()->pharmacien?->pharmacies()->first();
    return $pharmacie
        ? response()->json($pharmacie)
        : response()->json(['error' => 'Aucune pharmacie associée'], 404);
});

Route::middleware(['auth:sanctum', 'role:pharmacien'])->prefix('pharmacien')->group(function () {

    // Ma pharmacie
    Route::prefix('ma-pharmacie')->group(function () {
        Route::get('/', function(Request $request) {
            $pharmacie = $request->user()->pharmacien->pharmacies()->first();
            return $pharmacie
                ? response()->json($pharmacie)
                : response()->json(['error' => 'Aucune pharmacie associée'], 404);
        });

        Route::patch('/', function(Request $request) {
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
                'est_de_garde' => 'sometimes|boolean',
                'latitude' => 'sometimes|numeric|between:-90,90',
                'longitude' => 'sometimes|numeric|between:-180,180'
            ]);

            $pharmacie->update($validated);
            return response()->json([
                'message' => 'Pharmacie mise à jour avec succès',
                'pharmacie' => $pharmacie
            ]);
        });
    });

    // Gestion des pharmacies (CRUD complet)
    Route::prefix('pharmacies')->group(function () {
        Route::post('/', [PharmacieController::class, 'store']);
        Route::patch('/{pharmacie}', [PharmacieController::class, 'update']);
        Route::delete('/{pharmacie}', [PharmacieController::class, 'destroy']);

        // Mise à jour coordonnées GPS
        Route::patch('/{pharmacie}/coordonnees', function(Request $request, Pharmacie $pharmacie) {
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

        // Gestion des stocks par pharmacie
        Route::prefix('/{pharmacie}/stocks')->middleware('pharmacie.validee')->group(function () {
            Route::get('/', [StockController::class, 'index']);
            Route::patch('/{produit}', [StockController::class, 'update']);
            Route::post('/{produit}/incrementer', [StockController::class, 'incrementer']);
            Route::post('/{produit}/decrementer', [StockController::class, 'decrementer']);
        });
    });

    // Import de stock Excel
    Route::post('/importer-stock', [StockImportController::class, 'importerStock'])->middleware('pharmacie.validee');
    Route::post('/importer-stock-excel', [StockImportController::class, 'importerDepuisExcel'])->middleware('pharmacie.validee');

    // Gestion produits de ma pharmacie
    Route::prefix('mes-produits')->middleware('pharmacie.validee')->group(function () {
        Route::get('/', [PharmacienProduitController::class, 'index']);
        Route::get('/nouveau-code', [PharmacienProduitController::class, 'genererNouveauCode']);
        Route::post('/', [PharmacienProduitController::class, 'store']);
        Route::patch('/{produit}', [PharmacienProduitController::class, 'update']);
        Route::delete('/{produit}', [PharmacienProduitController::class, 'destroy']);
    });

    // Gestion ordonnances
    Route::prefix('ordonnances')->middleware('pharmacie.validee')->group(function () {
        Route::get('/', [OrdonnanceController::class, 'index']);
        Route::get('/{ordonnance}/image', [OrdonnanceController::class, 'voirImage']);
        Route::patch('/{ordonnance}/traiter', [OrdonnanceController::class, 'traiterOrdonnance']);
        Route::patch('/{ordonnance}/valider', [OrdonnanceController::class, 'valider']);
        Route::post('/{ordonnance}/creer-reservation', [OrdonnanceController::class, 'creerReservationAvecProduits']);
        Route::patch('/{ordonnance}/rejeter', [OrdonnanceController::class, 'rejeter']);
        Route::post('/{ordonnance}/remarques', [OrdonnanceController::class, 'ajouterRemarques']);
    });

    // Gestion réservations
    Route::prefix('reservations')->middleware('pharmacie.validee')->group(function () {
        Route::patch('/{reservation}/confirmer', [ReservationController::class, 'confirmerRetrait']);
        Route::patch('/{reservation}/valider', [ReservationController::class, 'validerAchat']);
    });

    // Conseils santé (création/modification)
    Route::prefix('conseils-sante')->group(function () {
        Route::get('/', [ConseilSanteController::class, 'mesConseils']);
        Route::post('/', [ConseilSanteController::class, 'store']);
        Route::patch('/{conseilSante}', [ConseilSanteController::class, 'update']);
        Route::delete('/{conseilSante}', [ConseilSanteController::class, 'destroy']);
    });

    // Debug auth
    Route::get('/debug-auth', function(Request $request) {
        return response()->json([
            'authenticated' => true,
            'user' => $request->user()->only(['id', 'nom', 'email', 'role']),
            'pharmacien' => $request->user()->pharmacien,
            'pharmacies' => $request->user()->pharmacien?->pharmacies,
            'token_valid' => true
        ]);
    });

    // Debug statut pharmacie
    Route::get('/statut-pharmacie', function(Request $request) {
        $user = $request->user();
        $pharmacien = $user->pharmacien;

        if (!$pharmacien) {
            return response()->json([
                'success' => false,
                'has_pharmacien_profile' => false,
                'message' => 'Profil pharmacien non trouvé'
            ]);
        }

        $pharmacie = $pharmacien->pharmacies()->first();

        if (!$pharmacie) {
            return response()->json([
                'success' => false,
                'has_pharmacien_profile' => true,
                'has_pharmacie' => false,
                'message' => 'Aucune pharmacie enregistrée',
                'action' => 'Enregistrez votre pharmacie via /api/pharmacien/enregistrer-pharmacie'
            ]);
        }

        return response()->json([
            'success' => true,
            'has_pharmacien_profile' => true,
            'has_pharmacie' => true,
            'pharmacie' => [
                'id' => $pharmacie->id,
                'nom' => $pharmacie->nom_pharmacie,
                'statut_validation' => $pharmacie->statut_validation,
                'is_validated' => $pharmacie->statut_validation === 'approved'
            ],
            'can_access_mes_produits' => $pharmacie->statut_validation === 'approved',
            'message' => $pharmacie->statut_validation === 'approved'
                ? 'Votre pharmacie est validée. Vous pouvez accéder à toutes les fonctionnalités.'
                : 'Votre pharmacie est en attente de validation par l\'autorité de santé.'
        ]);
    });
});

// ============================================
// ROUTES AUTORITÉ DE SANTÉ
// ============================================

Route::middleware(['auth:sanctum', 'role:autorite_sante'])->prefix('autorite')->group(function () {

    // Validation des demandes
    Route::prefix('demandes')->group(function () {
        Route::get('/pharmaciens', [ValidationController::class, 'demandesPharmaciens']);
        Route::get('/pharmacies', [ValidationController::class, 'demandesPharmacies']);
        Route::patch('/pharmacien/{user}/valider', [ValidationController::class, 'validerPharmacien']);
        Route::patch('/pharmacien/{user}/rejeter', [ValidationController::class, 'rejeterPharmacien']);
        Route::patch('/pharmacie/{pharmacie}/valider', [ValidationController::class, 'validerPharmacie']);
        Route::patch('/pharmacie/{pharmacie}/rejeter', [ValidationController::class, 'rejeterPharmacie']);
    });

    // Liste des pharmacies
    Route::get('/pharmacies', [ValidationController::class, 'listePharmacies']);
    
    // Consultation des documents justificatifs
    Route::get('/pharmacie/{pharmacie}/documents', [ValidationController::class, 'voirDocuments']);
    

    
    // Gestion des sanctions
    Route::prefix('sanctions')->group(function () {
        Route::patch('/pharmacie/{pharmacie}/bloquer', [AutoriteSanteController::class, 'bloquerPharmacie']);
        Route::patch('/pharmacie/{pharmacie}/suspendre', [AutoriteSanteController::class, 'suspendrePharmacie']);
        Route::patch('/pharmacie/{pharmacie}/debloquer', [AutoriteSanteController::class, 'debloquerPharmacie']);
        Route::get('/pharmacies-sanctionnees', [AutoriteSanteController::class, 'pharmaciesSanctionnees']);
    });

    // Rapports et contrôles
    Route::prefix('rapports')->group(function () {
        Route::get('/dispensation', [AutoriteSanteController::class, 'rapportDispensation']);
        Route::get('/consommation', [AutoriteSanteController::class, 'rapportConsommation']);
        Route::get('/statistiques', [AutoriteSanteController::class, 'statistiquesConsommation']);
    });

    // Audits
    Route::prefix('audits')->group(function () {
        Route::get('/pharmacies', [AutoriteSanteController::class, 'auditPharmacies']);
        Route::get('/ordonnances', [AutoriteSanteController::class, 'auditOrdonnances']);
        Route::get('/prescriptions-suspectes', [AutoriteSanteController::class, 'prescriptionsSuspectes']);
    });

    // Contrôles de conformité
    Route::post('/controle-conformite', [AutoriteSanteController::class, 'controleConformite']);
    Route::post('/signaler-anomalie', [AutoriteSanteController::class, 'signalerAnomalie']);

    // Export de données
    Route::get('/export/dispensation', [AutoriteSanteController::class, 'exportDispensation']);
    Route::get('/export/pharmacies', [AutoriteSanteController::class, 'exportPharmacies']);
});

// ============================================
// ROUTES ADMINISTRATEUR
// ============================================

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {

    // Dashboard & Statistiques
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/statistiques', [AdminController::class, 'statistiquesUtilisation']);
    Route::get('/statistiques/globales', [AdminController::class, 'statistiquesGlobales']);

    // Gestion utilisateurs
    Route::prefix('utilisateurs')->group(function () {
        Route::get('/', [AdminController::class, 'listeUtilisateurs']);
        Route::post('/', [AdminController::class, 'creerUtilisateur']);
        Route::get('/{user}', [AdminController::class, 'detailUtilisateur']);
        Route::patch('/{user}', [AdminController::class, 'gererUtilisateur']);
        Route::delete('/{user}', [AdminController::class, 'supprimerUtilisateur']);
        Route::patch('/{user}/activer', [AdminController::class, 'activerUtilisateur']);
        Route::patch('/{user}/desactiver', [AdminController::class, 'desactiverUtilisateur']);
    });

    // Gestion pharmacies (contrôle admin)
    Route::prefix('pharmacies')->group(function () {
        Route::get('/', [AdminController::class, 'listePharmacies']);
        Route::patch('/{pharmacie}/activer', [AdminController::class, 'activerPharmacie']);
        Route::patch('/{pharmacie}/desactiver', [AdminController::class, 'desactiverPharmacie']);
    });

    // Paramètres système
    Route::prefix('parametres')->group(function () {
        Route::get('/', [AdminController::class, 'parametresSysteme']);
        Route::post('/', [AdminController::class, 'mettreAJourParametres']);
    });

    // Logs & Monitoring
    Route::prefix('logs')->group(function () {
        Route::get('/', [AdminController::class, 'logsSysteme']);
        Route::get('/erreurs', [AdminController::class, 'logsErreurs']);
        Route::get('/activites', [AdminController::class, 'logsActivites']);
    });

    // Modération contenu
    Route::prefix('moderation')->group(function () {
        Route::get('/conseils-sante', [AdminController::class, 'conseilsSanteAModerer']);
        Route::patch('/conseils-sante/{conseilSante}/approuver', [AdminController::class, 'approuverConseil']);
        Route::patch('/conseils-sante/{conseilSante}/rejeter', [AdminController::class, 'rejeterConseil']);
    });

    // Sauvegarde & Maintenance
    Route::post('/sauvegarde', [AdminController::class, 'creerSauvegarde']);
    Route::get('/maintenance/activer', [AdminController::class, 'activerMaintenance']);
    Route::get('/maintenance/desactiver', [AdminController::class, 'desactiverMaintenance']);
});

// ============================================
// ROUTES DE TEST / DÉVELOPPEMENT
// ===============================



if (app()->environment('local', 'development')) {
    Route::prefix('test')->group(function () {
        Route::get('/seed-data', function() {
            Artisan::call('db:seed');
            return response()->json(['message' => 'Base de données remplie']);
        });

        Route::get('/clear-cache', function() {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            return response()->json(['message' => 'Cache vidé']);
        });
        
        Route::post('/debug-user-creation', function(Request $request) {
            return response()->json([
                'received_data' => $request->all(),
                'headers' => $request->headers->all()
            ]);
        });
    });
}

// ============================================
// ROUTE DE FALLBACK (404)
// ============================================

Route::fallback(function() {
    return response()->json([
        'success' => false,
        'message' => 'Route non trouvée',
        'error' => 'Endpoint inexistant'
    ], 404);
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


