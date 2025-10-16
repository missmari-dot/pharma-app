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
        Schema::create('produit_pharmacie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained()->onDelete('cascade');
            $table->foreignId('pharmacie_id')->constrained()->onDelete('cascade');
            $table->integer('quantite_disponible')->default(0);
            $table->timestamps();
            
            $table->unique(['produit_id', 'pharmacie_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produit_pharmacie');
    }
};
