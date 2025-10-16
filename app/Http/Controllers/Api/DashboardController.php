<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Ordonnance;
use App\Models\Reservation;
use App\Models\Pharmacie;
use App\Models\Produit;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        switch ($user->role) {
            case 'client':
                return app(ClientDashboardController::class)->dashboard($request);
            case 'pharmacien':
                return app(PharmacienDashboardController::class)->dashboard($request);
            case 'admin':
                return $this->dashboardAdmin($user);
            case 'autorite_sante':
                return app(AutoriteSanteDashboardController::class)->dashboard($request);
            default:
                return response()->json(['error' => 'Rôle non reconnu'], 403);
        }
    }





    private function dashboardAdmin(User $user)
    {
        $stats = [
            'role' => 'admin',
            'utilisateur' => [
                'nom' => $user->nom,
                'email' => $user->email,
                'privileges' => 'Administrateur Système'
            ],
            'statistiques_globales' => [
                'utilisateurs_total' => User::count(),
                'clients' => User::where('role', 'client')->count(),
                'pharmaciens' => User::where('role', 'pharmacien')->count(),
                'pharmacies' => Pharmacie::count(),
                'nouveaux_utilisateurs_semaine' => User::where('created_at', '>=', now()->subWeek())->count()
            ],
            'activite_systeme' => [
                'ordonnances_aujourd_hui' => Ordonnance::whereDate('created_at', today())->count(),
                'reservations_aujourd_hui' => Reservation::whereDate('created_at', today())->count(),
                'connexions_actives' => User::where('updated_at', '>=', now()->subHour())->count()
            ],
            'alertes_systeme' => $this->alertesSysteme(),
            'pharmacies_actives' => Pharmacie::whereHas('ordonnances', function($q) {
                $q->whereDate('created_at', today());
            })->count(),
            'notifications_non_lues' => $this->notificationsNonLues($user)
        ];

        return response()->json($stats);
    }











    private function alertesSysteme()
    {
        return [
            'utilisateurs_suspendus' => User::whereNull('email_verified_at')->count(),
            'ordonnances_en_attente' => Ordonnance::where('statut', 'ENVOYEE')
                ->where('created_at', '<', now()->subHours(24))
                ->count(),
            'erreurs_systeme' => 0 // À implémenter avec logs
        ];
    }

    private function notificationsNonLues(User $user)
    {
        return \DB::table('notifications')
            ->where('user_id', $user->id)
            ->where('lu', false)
            ->count();
    }
}