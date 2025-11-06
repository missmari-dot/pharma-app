<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pharmacie;
use Illuminate\Http\Request;

class ValidationController extends Controller
{
    public function demandesPharmaciens()
    {
        return User::where('role', 'pharmacien')
            ->where('statut', 'pending')
            ->with('pharmacien')
            ->get();
    }

    public function demandesPharmacies()
    {
        return Pharmacie::where('statut_validation', 'pending')
            ->with('pharmacien.user')
            ->get();
    }

    public function validerPharmacien(Request $request, User $user)
    {
        $request->validate([
            'statut' => 'required|in:approved,rejected'
        ]);

        $user->update(['statut' => $request->statut]);

        return response()->json([
            'message' => 'Statut mis à jour',
            'user' => $user
        ]);
    }

    public function validerPharmacie(Request $request, Pharmacie $pharmacie)
    {
        $pharmacie->update(['statut_validation' => 'approved']);

        return response()->json([
            'message' => 'Pharmacie validée',
            'pharmacie' => $pharmacie
        ]);
    }

    public function rejeterPharmacien(Request $request, User $user)
    {
        $user->update(['statut' => 'rejected']);
        return response()->json(['message' => 'Pharmacien rejeté']);
    }

    public function rejeterPharmacie(Request $request, Pharmacie $pharmacie)
    {
        $pharmacie->update(['statut_validation' => 'rejected']);
        return response()->json(['message' => 'Pharmacie rejetée']);
    }

    public function listePharmacies()
    {
        return Pharmacie::with('pharmacien.user')->get();
    }
    
    public function voirDocuments(Pharmacie $pharmacie)
    {
        if (!$pharmacie->documents_justificatifs) {
            return response()->json(['message' => 'Aucun document disponible'], 404);
        }
        
        $filePath = storage_path('app/public/' . $pharmacie->documents_justificatifs);
        
        if (!file_exists($filePath)) {
            return response()->json(['message' => 'Document non trouvé'], 404);
        }
        
        return response()->file($filePath);
    }
}