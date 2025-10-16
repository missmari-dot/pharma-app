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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('statut', ['pending', 'approved', 'rejected'])->default('pending');
        });
        
        Schema::table('pharmacies', function (Blueprint $table) {
            $table->enum('statut', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('numero_agrement')->nullable();
            $table->string('documents_justificatifs')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('statut');
        });
        
        Schema::table('pharmacies', function (Blueprint $table) {
            $table->dropColumn(['statut', 'numero_agrement', 'documents_justificatifs']);
        });
    }
};
