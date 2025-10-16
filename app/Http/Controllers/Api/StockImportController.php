<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use Illuminate\Http\Request;

class StockImportController extends Controller
{
    public function importerStock(Request $request)
    {
        $request->validate([
            'fichier_csv' => 'required|file|mimes:csv,txt|max:5120',
            'pharmacie_id' => 'required|exists:pharmacies,id'
        ]);

        $pharmacien = $request->user()->pharmacien;
        $pharmacie = $pharmacien->pharmacies()->find($request->pharmacie_id);
        
        if (!$pharmacie) {
            return response()->json(['message' => 'Pharmacie non autorisée'], 403);
        }

        $file = $request->file('fichier_csv');
        $handle = fopen($file->getPathname(), 'r');
        
        $imported = 0;
        $errors = [];
        $lineNumber = 0;
        
        // Skip header
        fgetcsv($handle);
        
        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            $lineNumber++;
            
            if (count($data) < 3) continue;
            
            $codeProduit = trim($data[0]);
            $libelle = trim($data[1]);
            $quantite = (int)trim($data[2]);
            
            try {
                $produit = Produit::firstOrCreate(
                    ['nom_produit' => $libelle],
                    ['prix' => 0, 'categorie' => 'Médicament', 'stock' => 0]
                );
                
                $pharmacie->produits()->syncWithoutDetaching([
                    $produit->id => ['quantite_disponible' => $quantite]
                ]);
                
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Ligne {$lineNumber}: {$e->getMessage()}";
            }
        }
        
        fclose($handle);
        
        return response()->json([
            'message' => "Import terminé: {$imported} produits importés",
            'errors' => $errors
        ]);
    }
}