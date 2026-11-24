<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * % de descuento por hermanos ("promo" en la UI) y % de beca, configurables
 * libremente por alumno desde su ficha. Se aplican SOLO sobre las cuotas
 * mensuales (CuotaAlumno::montoDescuento()), nunca sobre la matrícula.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('persona', function (Blueprint $table) {
            $table->decimal('porcentaje_promo', 5, 2)->default(0)->after('localidad');
            $table->decimal('porcentaje_beca', 5, 2)->default(0)->after('porcentaje_promo');
        });
    }

    public function down(): void
    {
        Schema::table('persona', function (Blueprint $table) {
            $table->dropColumn(['porcentaje_promo', 'porcentaje_beca']);
        });
    }
};
