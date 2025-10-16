<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('autorite_sante', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('code_autorisation')->unique();
            $table->enum('type_controle', ['MEDICAMENT', 'PHARMACIE', 'ORDONNANCE']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('autorite_sante');
    }
};