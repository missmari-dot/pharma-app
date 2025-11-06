<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PharmacieValidee
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user->role === 'pharmacien') {
            $pharmacien = $user->pharmacien;

            if (!$pharmacien) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil pharmacien non trouvé',
                    'error' => 'Votre compte utilisateur n\'est pas lié à un profil pharmacien. Veuillez contacter l\'administrateur.'
                ], 404);
            }

            $pharmacie = $pharmacien->pharmacies()->first();

            if (!$pharmacie) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune pharmacie enregistrée',
                    'error' => 'Vous devez d\'abord enregistrer votre pharmacie avant d\'accéder à cette fonctionnalité.',
                    'action_requise' => 'Enregistrez votre pharmacie via /api/pharmacien/enregistrer-pharmacie'
                ], 403);
            }

            if ($pharmacie->statut_validation !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pharmacie non validée',
                    'error' => 'Votre pharmacie doit être validée par l\'autorité de santé avant d\'accéder à cette fonctionnalité.',
                    'statut_actuel' => $pharmacie->statut_validation,
                    'info' => 'Votre demande est en cours de traitement. Vous serez notifié une fois la validation effectuée.'
                ], 403);
            }
        }

        return $next($request);
    }
}