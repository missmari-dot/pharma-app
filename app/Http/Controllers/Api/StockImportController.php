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
            
            // Vérifier si c'est un fichier Excel (.xlsx)
            if ($file->getClientOriginalExtension() === 'xlsx') {
                return response()->json([
                    'success' => false,
                    'message' => 'Les fichiers Excel (.xlsx) ne sont pas supportés. Veuillez exporter en CSV.'
                ], 400);
            }
            
            $content = file_get_contents($file->getPathname());
            
            // Vérifier si le contenu semble être du texte
            if (!mb_check_encoding($content, 'UTF-8') && !mb_check_encoding($content, 'ISO-8859-1')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le fichier semble corrompu ou n\'est pas un fichier CSV valide.'
                ], 400);
            }
            
            // Détecter et convertir l'encodage
            $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            }
            
            $imported = 0;
            $errors = [];
            $lineNumber = 0;
            
            // Lire tout le contenu et traiter ligne par ligne
            $lines = explode("\n", $content);
            
            // Debug: voir le contenu
            \Log::info('Nombre de lignes: ' . count($lines));
            \Log::info('Première ligne: ' . ($lines[0] ?? 'vide'));
            \Log::info('Deuxième ligne: ' . ($lines[1] ?? 'vide'));
            
            // Skip header
            array_shift($lines);
            
            foreach ($lines as $line) {
                $lineNumber++;
                
                if (empty(trim($line))) continue;
                
                // Essayer différents délimiteurs
                $data = str_getcsv($line, ';'); // Point-virgule d'abord
                if (count($data) < 2) {
                    $data = str_getcsv($line, ','); // Puis virgule
                }
                if (count($data) < 2) {
                    $data = str_getcsv($line, "\t"); // Puis tabulation
                }
                
                \Log::info("Ligne {$lineNumber}: " . json_encode($data));
                
                if (count($data) < 2) {
                    \Log::info("Ligne {$lineNumber} ignorée: moins de 2 colonnes");
                    continue;
                }
                
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