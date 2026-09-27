<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_estudio', function (Blueprint $table) {
            $table->id('id_material');
            $table->string('titulo', 150);
            $table->string('categoria', 100);
            $table->string('url_archivo', 255);
            $table->dateTime('fecha_subida')->useCurrent();

            $table->foreignId('id_admin')
                ->constrained('usuario', 'id_usuario')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_estudio');
    }
};