<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pharmacie_produit', function (Blueprint $table) {
            $table->date('date_expiration')->nullable()->after('quantite_disponible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pharmacie_produit', function (Blueprint $table) {
            $table->dropColumn('date_expiration');
        });
    }
};
