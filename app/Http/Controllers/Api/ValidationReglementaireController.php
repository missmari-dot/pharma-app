<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ordonnance;
use App\Models\Medicament;
use App\Services\ValidationReglementaireService;
use Illuminate\Http\Request;

class ValidationReglementaireController extends Controller
{
    protected $validationService;

    public function __construct(ValidationReglementaireService $validationService)
    {
        $this->validationService = $validationService;
    }

    public function validerOrdonnance(Request $request, Ordonnance $ordonnance)
    {
        if ($request->user()->role !== 'pharmacien') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $resultat = $this->validationService->validerOrdonnance($ordonnance);

        return response()->json([
            'valide' => $resultat['valide'],
            'message' => $resultat['message'],
            'details' => $resultat['details'] ?? null
        ]);
    }

    public function verifierInteractions(Request $request)
    {
        $validated = $request->validate([
            'medicaments' => 'required|array|min:1',
            'medicaments.*' => 'required|exists:medicaments,id'
        ]);

        $interactions = $this->validationService->verifierInteractions($validated['medicaments']);

        return response()->json([
            'interactions' => $interactions
        ]);
    }

    public function verifierPosologie(Request $request, Medicament $medicament)
    {
        $validated = $request->validate([
            'posologie_demandee' => 'required|string',
            'age_patient' => 'nullable|integer|min:0|max:120',
            'poids_patient' => 'nullable|numeric|min:0'
        ]);

        $resultat = $this->validationService->verifierPosologie(
            $medicament,
            $validated['posologie_demandee'],
            $validated['age_patient'] ?? null,
            $validated['poids_patient'] ?? null
        );

        return response()->json([
            'valide' => $resultat['valide'],
            'message' => $resultat['message'],
            'recommandations' => $resultat['recommandations'] ?? []
        ]);
    }
}
