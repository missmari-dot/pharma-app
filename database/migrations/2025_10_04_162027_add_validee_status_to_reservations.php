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
        DB::statement("ALTER TABLE reservations MODIFY COLUMN statut ENUM('en_attente', 'confirmee', 'prete', 'recuperee', 'validee', 'annulee') DEFAULT 'en_attente'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE reservations MODIFY COLUMN statut ENUM('en_attente', 'confirmee', 'prete', 'recuperee', 'annulee') DEFAULT 'en_attente'");
    }
};
