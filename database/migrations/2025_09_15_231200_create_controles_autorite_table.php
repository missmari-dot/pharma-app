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
        Schema::create('controles_autorite', function (Blueprint $table) {
            $table->id();
            $table->foreignId('autorite_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('pharmacie_id')->constrained('pharmacies')->onDelete('cascade');
            $table->enum('type_controle', ['ROUTINE', 'ALERTE', 'PLAINTE']);
            $table->enum('resultat', ['CONFORME', 'NON_CONFORME', 'EN_COURS']);
            $table->text('observations')->nullable();
            $table->json('criteres_evalues')->nullable();
            $table->date('date_controle');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('controles_autorite');
    }
};
