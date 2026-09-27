<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->string('nombre', 100);
            $table->string('email', 100)->unique();
            $table->string('password', 255);
            $table->boolean('estado')->default(true);

            $table->foreignId('id_rol')
                ->constrained('rol', 'id_rol')
                ->restrictOnDelete();

            $table->foreignId('id_casa_asignada')
                ->nullable()
                ->constrained('casa_iglesia', 'id_casa')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario');
    }
};