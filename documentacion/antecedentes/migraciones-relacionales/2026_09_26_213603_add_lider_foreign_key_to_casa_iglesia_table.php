<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('casa_iglesia', function (Blueprint $table) {
            $table->foreign('id_lider_responsable')
                ->references('id_usuario')
                ->on('usuario')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('casa_iglesia', function (Blueprint $table) {
            $table->dropForeign(['id_lider_responsable']);
        });
    }
};