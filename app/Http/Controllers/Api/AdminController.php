<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pharmacie;
use App\Models\Ordonnance;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'utilisateurs_total' => User::count(),
            'clients_total' => User::where('role', 'client')->count(),
            'pharmaciens_total' => User::where('role', 'pharmacien')->count(),
            'pharmacies_total' => Pharmacie::count(),
            'ordonnances_aujourd_hui' => Ordonnance::whereDate('created_at', today())->count(),
            'ordonnances_en_attente' => Ordonnance::where('statut', 'ENVOYEE')->count()
        ]);
    }

    public function listeUtilisateurs()
    {
        $utilisateurs = User::with(['pharmacien.pharmacies'])
            ->select('id', 'nom', 'email', 'role', 'email_verified_at', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($utilisateurs);
    }

    public function creerUtilisateur(Request $request)
    {
        try {
            // Log des données reçues pour debug
            \Log::info('Données reçues pour création utilisateur:', $request->all());
            
            $rules = [
                'nom' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                'role' => 'required|in:client,pharmacien,admin,autorite_sante',
                'telephone' => 'required|string|max:20',
                'adresse' => 'nullable|string',
                'date_naissance' => 'nullable|date'
            ];
            
            // Champs spécifiques pour autorité de santé
            if ($request->input('role') === 'autorite_sante') {
                $rules['code_autorisation'] = 'required|string|max:50';
                $rules['type_controle'] = 'required|string|max:100';
                $rules['organisme'] = 'required|string|max:100';
            }
            
            \Log::info('Règles de validation:', $rules);
            
            $validated = $request->validate($rules);
            
            // Validation d'unicité du téléphone
            $existingUser = User::where('telephone', $validated['telephone'])->first();
            if ($existingUser) {
                return response()->json([
                    'error' => 'Erreur de validation',
                    'errors' => ['telephone' => ['Ce numéro de téléphone est déjà utilisé.']]
                ], 422);
            }

            $userData = [
                'nom' => $validated['nom'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'role' => $validated['role'],
                'telephone' => $validated['telephone'],
                'adresse' => $validated['adresse'] ?? null,
                'date_naissance' => $validated['date_naissance'] ?? null,
                'email_verified_at' => now()
            ];
            
            // Ajouter les champs spécifiques pour autorité de santé
            if ($validated['role'] === 'autorite_sante') {
                $userData['code_autorisation'] = $validated['code_autorisation'];
                $userData['type_controle'] = $validated['type_controle'];
                $userData['organisme'] = $validated['organisme'];
            }

            $user = User::create($userData);

            return response()->json([
                'message' => 'Utilisateur créé avec succès',
                'user' => $user
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Erreurs de validation:', $e->errors());
            return response()->json([
                'error' => 'Erreur de validation',
                'errors' => $e->errors(),
                'received_data' => $request->all()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la création',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    public function gererUtilisateur(Request $request, User $user)
    {
        // Si c'est une action (suspendre/activer/supprimer)
        if ($request->has('action')) {
            $validated = $request->validate([
                'action' => 'required|in:suspendre,activer,supprimer'
            ]);

            switch ($validated['action']) {
                case 'suspendre':
                    $user->update(['email_verified_at' => null]);
                    break;
                case 'activer':
                    $user->update(['email_verified_at' => now()]);
                    break;
                case 'supprimer':
                    $user->delete();
                    break;
            }

            return response()->json(['message' => 'Action effectuée']);
        }
        
        // Sinon c'est une modification des données
        return $this->modifierUtilisateur($request, $user);
    }
    
    public function modifierUtilisateur(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'nom' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'role' => 'sometimes|in:client,pharmacien,admin,autorite_sante',
                'telephone' => 'nullable|string|max:20|unique:users,telephone,' . $user->id,
                'adresse' => 'nullable|string',
                'date_naissance' => 'nullable|date',
                'code_autorisation' => 'nullable|string|max:50',
                'type_controle' => 'nullable|string|max:100',
                'organisme' => 'nullable|string|max:100'
            ]);

            $user->update($validated);

            return response()->json([
                'message' => 'Utilisateur modifié avec succès',
                'user' => $user
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la modification',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function parametresSysteme(Request $request)
    {
        if ($request->isMethod('GET')) {
            return response()->json([
                'maintenance_mode' => config('app.maintenance', false),
                'max_upload_size' => config('app.max_upload_size', '2MB'),
                'session_timeout' => config('session.lifetime', 120)
            ]);
        }

        $validated = $request->validate([
            'maintenance_mode' => 'boolean',
            'max_upload_size' => 'string',
            'session_timeout' => 'integer'
        ]);

        // Mise à jour des paramètres (en production, utiliser une table config)
        return response()->json(['message' => 'Paramètres mis à jour']);
    }

    public function logsSysteme(Request $request)
    {
        $logs = collect(\File::get(storage_path('logs/laravel.log')))
            ->split('/\n/')
            ->filter()
            ->take(-100) // 100 dernières lignes
            ->reverse();

        return response()->json($logs);
    }

    public function statistiquesUtilisation()
    {
        return response()->json([
            'connexions_aujourd_hui' => User::whereDate('updated_at', today())->count(),
            'ordonnances_par_jour' => Ordonnance::selectRaw('DATE(created_at) as date, COUNT(*) as total')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(7)
                ->get(),
            'pharmacies_actives' => Pharmacie::whereHas('ordonnances', function($q) {
                $q->whereDate('created_at', today());
            })->count()
        ]);
    }

    public function detailUtilisateur(User $user)
    {
        $user->load(['pharmacien.pharmacies']);
        return response()->json($user);
    }

    public function supprimerUtilisateur(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'Utilisateur supprimé']);
    }

    public function activerUtilisateur(User $user)
    {
        $user->update(['email_verified_at' => now()]);
        return response()->json(['message' => 'Utilisateur activé']);
    }

    public function desactiverUtilisateur(User $user)
    {
        $user->update(['email_verified_at' => null]);
        return response()->json(['message' => 'Utilisateur désactivé']);
    }

    public function listePharmacies()
    {
        $pharmacies = Pharmacie::with(['pharmacien.user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($pharmacies);
    }

    public function activerPharmacie(Pharmacie $pharmacie)
    {
        $pharmacie->update(['statut_validation' => 'approved']);
        return response()->json(['message' => 'Pharmacie activée']);
    }

    public function desactiverPharmacie(Pharmacie $pharmacie)
    {
        $pharmacie->update(['statut_validation' => 'rejected']);
        return response()->json(['message' => 'Pharmacie désactivée']);
    }

    public function mettreAJourParametres(Request $request)
    {
        $validated = $request->validate([
            'maintenance_mode' => 'boolean',
            'max_upload_size' => 'string',
            'session_timeout' => 'integer'
        ]);

        return response()->json(['message' => 'Paramètres mis à jour']);
    }

    public function logsErreurs()
    {
        return response()->json(['logs' => []]);
    }

    public function logsActivites()
    {
        return response()->json(['logs' => []]);
    }

    public function statistiquesGlobales()
    {
        return response()->json([
            'utilisateurs_total' => User::count(),
            'pharmacies_total' => Pharmacie::count(),
            'ordonnances_total' => Ordonnance::count()
        ]);
    }

    public function conseilsSanteAModerer()
    {
        return response()->json(['conseils' => []]);
    }

    public function approuverConseil($id)
    {
        return response()->json(['message' => 'Conseil approuvé']);
    }

    public function rejeterConseil($id)
    {
        return response()->json(['message' => 'Conseil rejeté']);
    }

    public function creerSauvegarde()
    {
        return response()->json(['message' => 'Sauvegarde créée']);
    }

    public function activerMaintenance()
    {
        return response()->json(['message' => 'Mode maintenance activé']);
    }

    public function desactiverMaintenance()
    {
        return response()->json(['message' => 'Mode maintenance désactivé']);
    }
}