<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vínculo "hermano/a" entre dos alumnos (tabla persona), simétrico: al
 * vincular se guardan ambas direcciones (A->B y B->A) para poder consultar
 * Persona::hermanos() desde cualquiera de los dos sin una relación inversa
 * aparte. Se usa para decidir si corresponde aplicar el % de promo
 * (Persona::tieneHermanoActivo()) — si el hermano vinculado se da de baja,
 * la promo deja de aplicarse sola.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hermano_alumno', function (Blueprint $table) {
            $table->foreignId('id_persona')->constrained('persona', 'id_persona');
            $table->foreignId('id_persona_hermano')->constrained('persona', 'id_persona');
            $table->primary(['id_persona', 'id_persona_hermano']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hermano_alumno');
    }
};
