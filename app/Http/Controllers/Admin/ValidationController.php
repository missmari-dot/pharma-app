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
        return Pharmacie::where('statut', 'pending')
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
        $request->validate([
            'statut' => 'required|in:approved,rejected'
        ]);

        $pharmacie->update(['statut' => $request->statut]);

        return response()->json([
            'message' => 'Statut mis à jour',
            'pharmacie' => $pharmacie
        ]);
    }
}