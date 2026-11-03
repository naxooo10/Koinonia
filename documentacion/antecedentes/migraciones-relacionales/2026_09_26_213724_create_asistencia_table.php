<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencia', function (Blueprint $table) {
            $table->foreignId('id_reunion')
                ->constrained('reunion', 'id_reunion')
                ->cascadeOnDelete();

            $table->foreignId('id_usuario')
                ->constrained('usuario', 'id_usuario')
                ->restrictOnDelete();

            $table->primary(['id_reunion', 'id_usuario']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencia');
    }
};