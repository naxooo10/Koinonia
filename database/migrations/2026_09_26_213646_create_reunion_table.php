<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reunion', function (Blueprint $table) {
            $table->id('id_reunion');
            $table->date('fecha');
            $table->string('tema', 150);

            $table->foreignId('id_casa')
                ->constrained('casa_iglesia', 'id_casa')
                ->restrictOnDelete();

            $table->index(['fecha', 'id_casa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reunion');
    }
};