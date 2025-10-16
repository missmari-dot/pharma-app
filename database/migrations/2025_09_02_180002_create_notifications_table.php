<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('titre');
            $table->text('message');
            $table->string('type');
            $table->json('data')->nullable();
            $table->boolean('lu')->default(false);
            $table->timestamps();
            
            $table->index(['user_id', 'lu']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};