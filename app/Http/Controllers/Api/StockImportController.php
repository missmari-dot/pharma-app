<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function importerDepuisExcel(Request $request)
    {
        $request->validate([
            'fichier_excel' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120'
        ]);

        $pharmacien = $request->user()->pharmacien;
        $pharmacie = $pharmacien->pharmacies()->first();
        
        if (!$pharmacie) {
            return response()->json(['message' => 'Aucune pharmacie associée'], 404);
        }

        try {
            $file = $request->file('fichier_excel');
            $handle = fopen($file->getPathname(), 'r');
            
            if (!$handle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de lire le fichier'
                ], 400);
            }
            
            $imported = 0;
            $errors = [];
            $lineNumber = 0;
            
            // Skip header
            fgetcsv($handle);
            
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                $lineNumber++;
                
                if (count($data) < 2) continue;
                
                $codeProduit = trim($data[0] ?? '');
                $nomProduit = trim($data[1] ?? '');
                $quantite = (int)($data[2] ?? 0);
                $prix = (float)($data[3] ?? 1000);
                
                if (empty($nomProduit)) {
                    $errors[] = "Ligne {$lineNumber}: Nom du produit manquant";
                    continue;
                }
                
                try {
                    $produit = null;
                    
                    // Recherche par code produit d'abord
                    if (!empty($codeProduit)) {
                        $produit = Produit::where('code_produit', $codeProduit)->first();
                    }
                    
                    // Sinon recherche par nom
                    if (!$produit) {
                        $produit = Produit::where('nom_produit', $nomProduit)->first();
                    }
                    
                    // Créer le produit s'il n'existe pas
                    if (!$produit) {
                        $nextId = Produit::max('id') + 1;
                        $produit = Produit::create([
                            'code_produit' => $codeProduit ?: 'PROD' . str_pad($nextId, 6, '0', STR_PAD_LEFT),
                            'nom_produit' => $nomProduit,
                            'prix' => $prix,
                            'categorie' => 'Médicament',
                            'stock' => 0
                        ]);
                    }
                    
                    // Ajouter/mettre à jour le stock dans la pharmacie
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
                'success' => true,
                'message' => "Import terminé: {$imported} produits importés",
                'imported' => $imported,
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'import: ' . $e->getMessage()
            ], 500);
        }
    }
}