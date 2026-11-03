<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casa_iglesia', function (Blueprint $table) {
            $table->id('id_casa');
            $table->string('nombre_casa', 100);
            $table->string('direccion', 200);
            $table->boolean('estado')->default(true);
            $table->unsignedBigInteger('id_lider_responsable')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casa_iglesia');
    }
};