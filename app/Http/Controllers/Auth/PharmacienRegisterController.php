<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pharmacien;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PharmacienRegisterController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'telephone' => 'required|string'
        ]);

        $user = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'pharmacien',
            'statut' => 'pending'
        ]);

        $pharmacien = Pharmacien::create([
            'user_id' => $user->id,
            'telephone' => $request->telephone
        ]);

        return response()->json([
            'message' => 'Inscription réussie. Votre compte est en attente de validation.',
            'user' => $user,
            'pharmacien' => $pharmacien
        ]);
    }
}