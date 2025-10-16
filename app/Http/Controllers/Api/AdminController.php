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

    public function gererUtilisateur(Request $request, User $user)
    {
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
}