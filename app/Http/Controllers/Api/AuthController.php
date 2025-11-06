<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Client;
use App\Models\Pharmacien;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'telephone' => 'required|string',
            'adresse' => 'required|string',
            'date_naissance' => 'required|date',
            'role' => 'required|in:client,pharmacien,admin,autorite_sante'
        ]);

        $user = User::create([
            'nom' => $validated['nom'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'telephone' => $validated['telephone'],
            'adresse' => $validated['adresse'],
            'date_naissance' => $validated['date_naissance'],
            'role' => $validated['role']
        ]);

        if ($validated['role'] === 'client') {
            Client::create(['user_id' => $user->id]);
        } elseif ($validated['role'] === 'pharmacien') {
            Pharmacien::create([
                'user_id' => $user->id,
                'pharmacies_associees' => ''
            ]);
        } elseif ($validated['role'] === 'autorite_sante') {
            \App\Models\AutoriteSante::create([
                'user_id' => $user->id,
                'code_autorite' => 'AS-' . strtoupper(substr(md5(uniqid()), 0, 8))
            ]);
        }
        // admin n'a pas besoin de profil spécifique

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'dashboard_url' => '/api/dashboard'
        ]);
    }

    public function login(Request $request)
    {
        try {
            \Log::info('Login attempt', $request->all());
            
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            $user = User::where('email', $validated['email'])->first();
            \Log::info('User found', ['user' => $user ? $user->email : 'not found']);

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return response()->json(['message' => 'Identifiants invalides'], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token,
                'role' => $user->role,
                'dashboard_url' => '/api/dashboard'
            ]);
        } catch (\Exception $e) {
            \Log::error('Login error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
            'role' => $request->user()->role,
            'dashboard_url' => '/api/dashboard'
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnecté']);
    }
}