<?php

namespace App\Http\Controllers;

use App\Models\Ordonnance;
use App\Services\UploadService;
use Illuminate\Http\Request;

class OrdonnanceController extends Controller
{
    protected $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'fichier_ordonnance' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'client_id' => 'required|exists:clients,id'
        ]);

        $filePath = $this->uploadService->uploadOrdonnance($request->file('fichier_ordonnance'));

        return Ordonnance::create([
            'client_id' => $request->client_id,
            'fichier_ordonnance' => $filePath,
            'statut' => 'en_attente'
        ]);
    }

    public function show(Ordonnance $ordonnance)
    {
        return response()->file(storage_path('app/public/' . $ordonnance->fichier_ordonnance));
    }
}
