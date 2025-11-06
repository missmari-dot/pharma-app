<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->string('code_produit', 20)->nullable()->after('id');
        });
        
        // Générer des codes pour les produits existants
        DB::table('produits')->get()->each(function($produit) {
            DB::table('produits')->where('id', $produit->id)
                ->update(['code_produit' => 'PROD' . str_pad($produit->id, 6, '0', STR_PAD_LEFT)]);
        });
        
        // Ajouter la contrainte unique après avoir généré les codes
        Schema::table('produits', function (Blueprint $table) {
            $table->string('code_produit', 20)->nullable(false)->change();
            $table->unique('code_produit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->dropColumn('code_produit');
        });
    }
};