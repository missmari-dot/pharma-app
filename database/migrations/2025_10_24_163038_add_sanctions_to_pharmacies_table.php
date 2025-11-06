<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacies', function (Blueprint $table) {
            $table->enum('statut_activite', ['active', 'suspendue', 'bloquee'])->default('active');
            $table->text('motif_sanction')->nullable()->after('statut_activite');
            $table->timestamp('date_sanction')->nullable()->after('motif_sanction');
            $table->timestamp('date_fin_sanction')->nullable()->after('date_sanction');
            $table->foreignId('sanctionnee_par')->nullable()->constrained('users')->after('date_fin_sanction');
        });
    }

    public function down(): void
    {
        Schema::table('pharmacies', function (Blueprint $table) {
            $table->dropColumn(['statut_activite', 'motif_sanction', 'date_sanction', 'date_fin_sanction', 'sanctionnee_par']);
        });
    }
};