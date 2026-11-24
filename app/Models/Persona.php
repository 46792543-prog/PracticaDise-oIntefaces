<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    use HasFactory;

    /** A partir de esta cantidad de meses adeudados (no hace falta que sean
     * consecutivos) el alumno queda bloqueado para inscribirse a cursar o a
     * rendir — ver Persona::superaLimiteDeuda(). */
    public const MESES_MAXIMOS_DEUDA_PARA_INSCRIBIRSE = 3;

    protected $table = 'persona';
    protected $primaryKey = 'id_persona';

    protected $fillable = [
        'dni', 'fecha_nacimiento', 'nombre', 'apellido', 'telefono', 'direccion', 'localidad',
        'porcentaje_promo', 'porcentaje_beca',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'porcentaje_promo' => 'decimal:2',
        'porcentaje_beca' => 'decimal:2',
    ];

    public function usuario()
    {
        return $this->hasOne(Usuario::class, 'id_persona', 'id_persona');
    }

    public function profesor()
    {
        return $this->hasOne(Profesor::class, 'id_persona', 'id_persona');
    }

    public function inscripcionesCarrera()
    {
        return $this->hasMany(InscripcionCarrera::class, 'id_persona_alumno', 'id_persona');
    }

    public function historialAlumno()
    {
        return $this->hasMany(HistorialAlumno::class, 'id_persona_alumno', 'id_persona');
    }

    public function cuotas()
    {
        return $this->hasMany(CuotaAlumno::class, 'id_persona_alumno', 'id_persona');
    }

    public function matriculas()
    {
        return $this->hasMany(MatriculaAlumno::class, 'id_persona_alumno', 'id_persona');
    }

    /**
     * Vínculo "hermano/a" con otros alumnos — simétrico (se guarda en ambas
     * direcciones al vincular, ver AlumnoController::vincularHermano()).
     * Permite varios hermanos por alumno.
     */
    public function hermanos()
    {
        return $this->belongsToMany(Persona::class, 'hermano_alumno', 'id_persona', 'id_persona_hermano');
    }

    /**
     * True si para ese año lectivo no hay matrícula generada (no se bloquea,
     * nunca se configuró) o si la que hay ya está pagada. False solo cuando
     * existe una matrícula generada y sigue impaga — ahí es cuando se
     * bloquea el cobro de cuotas de ese año (ver CuotaController::cobrar()).
     */
    public function matriculaPagada(int $idAnioLectivo): bool
    {
        $matricula = $this->matriculas()->where('id_anio_lectivo', $idAnioLectivo)->first();

        return ! $matricula || $matricula->pagado;
    }

    /**
     * True si al menos uno de los hermanos vinculados tiene una inscripción
     * a carrera activa. La promo depende de esto: si el hermano se da de
     * baja, deja de contar y la promo se cae sola sin que nadie la edite.
     */
    public function tieneHermanoActivo(): bool
    {
        return $this->hermanos->contains(
            fn (Persona $hermano) => $hermano->inscripcionesCarrera->contains(
                fn (InscripcionCarrera $i) => $i->estadoInscripcion?->nombre_estado === 'Activo'
            )
        );
    }

    /**
     * % total de descuento sobre cuotas mensuales (promo por hermanos + beca),
     * sumados y con tope de 100% para que el neto nunca sea negativo. La
     * promo solo cuenta si hay un hermano vinculado y activo (ver
     * tieneHermanoActivo()); la beca es siempre el valor cargado a mano.
     */
    public function porcentajeDescuentoCuotas(): float
    {
        $porcentajePromo = $this->tieneHermanoActivo() ? (float) $this->porcentaje_promo : 0.0;

        return min(100.0, $porcentajePromo + (float) $this->porcentaje_beca);
    }

    /**
     * Total de descuento (promo+beca) acumulado sobre las cuotas pendientes
     * del alumno. Usa la relación cuotas() ya cargada si está eager-loadeada,
     * para no disparar queries extra.
     */
    public function descuentoPendienteCuotas(): float
    {
        $porcentaje = $this->porcentajeDescuentoCuotas();

        return (float) $this->cuotas
            ->where('pagado', false)
            ->sum(fn (CuotaAlumno $cuota) => $cuota->montoDescuento($porcentaje));
    }

    public function inscripcionesMesa()
    {
        return $this->hasMany(InscripcionMesa::class, 'id_persona_alumno', 'id_persona');
    }

    public function documentacion()
    {
        return $this->hasMany(ControlDocumentacion::class, 'id_persona_alumno', 'id_persona');
    }

    public function acciones()
    {
        return $this->hasMany(Acciones::class, 'id_persona', 'id_persona');
    }

    public function equivalencias()
    {
        return $this->hasMany(Equivalencia::class, 'id_persona_alumno', 'id_persona');
    }

    public function fichaRespuestas()
    {
        return $this->hasMany(FichaRespuesta::class, 'id_persona_alumno', 'id_persona');
    }

    /**
     * Estado mes a mes (pagado o no) del ciclo lectivo activo, desde marzo
     * (o desde que se inscribió, si fue después) hasta $hastaMes — sin
     * importar si la cuota de cada mes llegó a generarse o no. Cada item es
     * ['mes' => Mes, 'pagado' => bool].
     */
    public function estadoMensualCuotas(?int $hastaMes = null): \Illuminate\Support\Collection
    {
        $anioLectivoActivo = AnioLectivo::whereHas('estadoAnio', fn ($q) => $q->where('nombre_estado', 'Activo'))->first();

        if (! $anioLectivoActivo) {
            return collect();
        }

        $inscripcion = $this->inscripcionesCarrera()
            ->where('id_anio_lectivo', $anioLectivoActivo->id_anio_lectivo)
            ->whereHas('estadoInscripcion', fn ($q) => $q->where('nombre_estado', 'Activo'))
            ->first();

        if (! $inscripcion) {
            return collect();
        }

        $inicioCiclo = max(3, $inscripcion->fecha_inscripcion->month); // el ciclo lectivo arranca en marzo
        $hastaMes ??= now()->month;

        if ($hastaMes < $inicioCiclo) {
            return collect();
        }

        $mesesPagados = $this->cuotas()
            ->where('id_anio_lectivo', $anioLectivoActivo->id_anio_lectivo)
            ->where('pagado', true)
            ->pluck('id_mes');

        return Mes::whereBetween('id_mes', [$inicioCiclo, min($hastaMes, 12)])
            ->orderBy('id_mes')
            ->get()
            ->map(fn (Mes $mes) => ['mes' => $mes, 'pagado' => $mesesPagados->contains($mes->id_mes)]);
    }

    /**
     * Igual que estadoMensualCuotas() pero devuelve solo los meses que
     * todavía debe (los no pagados).
     */
    public function mesesAdeudados(?int $hastaMes = null): \Illuminate\Support\Collection
    {
        return $this->estadoMensualCuotas($hastaMes)
            ->reject(fn ($item) => $item['pagado'])
            ->pluck('mes');
    }

    /**
     * True si el alumno acumula 3 o más meses adeudados (no hace falta que
     * sean consecutivos) — a partir de ahí queda bloqueado para inscribirse
     * a cursar materias o a rendir exámenes.
     */
    public function superaLimiteDeuda(): bool
    {
        return $this->mesesAdeudados()->count() >= self::MESES_MAXIMOS_DEUDA_PARA_INSCRIBIRSE;
    }

    /**
     * Meses que debe del año lectivo activo y que ni siquiera tienen la cuota
     * generada (no se pueden cobrar hasta generarla).
     */
    public function mesesSinCuotaGenerada(): \Illuminate\Support\Collection
    {
        $anioLectivoActivo = AnioLectivo::whereHas('estadoAnio', fn ($q) => $q->where('nombre_estado', 'Activo'))->first();

        if (! $anioLectivoActivo) {
            return collect();
        }

        $mesesConCuota = $this->cuotas()
            ->where('id_anio_lectivo', $anioLectivoActivo->id_anio_lectivo)
            ->pluck('id_mes');

        return $this->mesesAdeudados()
            ->reject(fn (Mes $mes) => $mesesConCuota->contains($mes->id_mes))
            ->values()
            ->each(fn (Mes $mes) => $mes->setAttribute('id_anio_lectivo', $anioLectivoActivo->id_anio_lectivo));
    }
}
