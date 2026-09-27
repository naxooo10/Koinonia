<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evento', function (Blueprint $table) {
            $table->id('id_evento');
            $table->string('titulo', 100);
            $table->text('descripcion');
            $table->dateTime('fecha_hora');

            $table->foreignId('id_creador')
                ->constrained('usuario', 'id_usuario')
                ->restrictOnDelete();

            $table->foreignId('id_casa')
                ->nullable()
                ->constrained('casa_iglesia', 'id_casa')
                ->restrictOnDelete();

            $table->index('fecha_hora');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evento');
    }
};