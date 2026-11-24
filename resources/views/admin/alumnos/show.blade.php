@extends('layouts.admin')

@section('titulo', 'Ficha del alumno')

@section('contenido')
    @php
        $inscripcion = $alumno->inscripcionesCarrera->first();
        $estadoInscNombre = $inscripcion?->estadoInscripcion?->nombre_estado;
        $cuotasPagadas = $alumno->cuotas->where('pagado', true)->count();
        $cuotasPendientes = $alumno->cuotas->where('pagado', false)->count();
        $cuotasVencidas = $alumno->cuotas->filter(fn ($c) => $c->vencida)->count();
    @endphp

    <a href="{{ route('admin.alumnos.index') }}" class="sg-link mb-4 sg-revelar"><x-sg.icono nombre="arrow-left" class="!h-4 !w-4" /> Volver al listado</a>

    {{-- Cabecera de la ficha --}}
    <section class="sg-card sg-revelar overflow-hidden mb-6">
        <div class="h-24 bg-gradient-to-r from-[#082A66] via-[#0B3D91] to-[#4FC3F7] relative overflow-hidden">
            <span class="absolute -right-10 -top-16 h-48 w-48 rounded-full border-[14px] border-[#FFC107]/25"></span>
            <span class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 18px 18px;"></span>
        </div>
        <div class="relative px-6 pb-6 flex items-end justify-between flex-wrap gap-5">
            <div class="flex items-end gap-4 min-w-0">
                <span class="sg-avatar sg-avatar--grande sg-avatar--marca h-20 w-20 shrink-0 -mt-10 text-2xl ring-4 ring-white shadow-lg">
                    {{ mb_substr($alumno->nombre, 0, 1) }}{{ mb_substr($alumno->apellido, 0, 1) }}
                </span>
                <div class="pt-3 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="font-display font-extrabold text-xl text-slate-900">{{ $alumno->apellido }}, {{ $alumno->nombre }}</h1>
                        @if ($estadoInscNombre === 'Activo')
                            <span class="sg-badge sg-badge--verde">Activo</span>
                        @elseif ($estadoInscNombre === 'Baja')
                            <span class="sg-badge sg-badge--rojo">Baja</span>
                        @endif
                    </div>
                    <p class="text-sm text-slate-500 mt-0.5">DNI {{ $alumno->dni }} · {{ $inscripcion?->carrera?->nombre_carrera ?? 'Sin carrera asignada' }}</p>
                </div>
            </div>
            <div class="flex gap-2 flex-wrap">
                <a href="{{ route('admin.documentacion.show', $alumno) }}" class="sg-btn sg-btn--borde sg-btn--chico"><x-sg.icono nombre="folder" /> Documentación</a>
                @if ($inscripcion && $inscripcion->estadoInscripcion->nombre_estado === 'Activo')
                    <a href="{{ route('admin.alumnos.materias', $alumno) }}" class="sg-btn sg-btn--azul sg-btn--chico"><x-sg.icono nombre="cap" /> Inscribir a materias</a>
                @endif
                @if ($progresoPromocion)
                    <form method="POST" action="{{ route('admin.alumnos.promover', $alumno) }}"
                          onsubmit="return confirm('¿Promover a {{ $alumno->nombre }} {{ $alumno->apellido }} a {{ $progresoPromocion['proximoAnio']->nombre_anio }}?');">
                        @csrf @method('PUT')
                        <button type="submit" @disabled(! $progresoPromocion['puede'])
                                title="{{ $progresoPromocion['aprobadas'] }} de {{ $progresoPromocion['total'] }} materias de su año aprobadas{{ $progresoPromocion['puede'] ? '' : ' — hace falta la mayoría para promover' }}"
                                @class(['sg-btn sg-btn--chico', 'sg-btn--verde' => $progresoPromocion['puede'], 'sg-btn--borde opacity-50 cursor-not-allowed' => ! $progresoPromocion['puede']])>
                            <x-sg.icono nombre="trending-up" />
                            Promover a {{ $progresoPromocion['proximoAnio']->nombre_anio }}
                            <span class="text-xs opacity-80">({{ $progresoPromocion['aprobadas'] }}/{{ $progresoPromocion['total'] }})</span>
                        </button>
                    </form>
                @endif
                @if ($inscripcion && $inscripcion->estadoInscripcion->nombre_estado === 'Activo')
                    <form method="POST" action="{{ route('admin.alumnos.baja', $alumno) }}" onsubmit="return confirm('¿Confirmás dar de baja a este alumno?');">
                        @csrf @method('PUT')
                        <button type="submit" class="sg-btn sg-btn--peligro sg-btn--chico"><x-sg.icono nombre="user-minus" /> Dar de baja</button>
                    </form>
                @elseif ($inscripcion && $inscripcion->estadoInscripcion->nombre_estado === 'Baja')
                    <form method="POST" action="{{ route('admin.alumnos.alta', $alumno) }}" onsubmit="return confirm('¿Confirmás dar de alta nuevamente a este alumno?');">
                        @csrf @method('PUT')
                        <button type="submit" class="sg-btn sg-btn--verde sg-btn--chico"><x-sg.icono nombre="user-plus" /> Dar de alta</button>
                    </form>
                @endif
            </div>
        </div>
    </section>

    <div class="grid lg:grid-cols-3 gap-6 mb-6" data-sg-escalonar>
        <div class="sg-card p-5">
            <div class="flex items-center gap-3 mb-4">
                <span class="sg-chip sg-chip--chico sg-chip--azul"><x-sg.icono nombre="user" /></span>
                <h3 class="font-display font-bold text-slate-800 text-sm">Datos personales</h3>
            </div>
            <dl class="text-sm space-y-3">
                <div class="flex justify-between gap-3"><dt class="text-slate-400 flex items-center gap-2"><x-sg.icono nombre="mail" class="h-4 w-4" /> Email</dt><dd class="text-slate-700 font-medium truncate">{{ $alumno->usuario->email ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-400 flex items-center gap-2"><x-sg.icono nombre="phone" class="h-4 w-4" /> Teléfono</dt><dd class="text-slate-700 font-medium">{{ $alumno->telefono ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-400 flex items-center gap-2"><x-sg.icono nombre="calendar" class="h-4 w-4" /> Nacimiento</dt><dd class="text-slate-700 font-medium">{{ \App\Support\FechaEsp::corta($alumno->fecha_nacimiento) }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-400 flex items-center gap-2"><x-sg.icono nombre="map" class="h-4 w-4" /> Localidad</dt><dd class="text-slate-700 font-medium">{{ $alumno->localidad ?? '—' }}</dd></div>
            </dl>
            <form method="POST" action="{{ route('admin.alumnos.beneficios', $alumno) }}" class="grid grid-cols-2 gap-3 mt-4 pt-4 border-t border-dashed border-slate-200">
                @csrf @method('PUT')
                <div>
                    <label class="text-xs font-semibold text-slate-400" title="Solo se aplica si tiene un hermano/a vinculado y activo (ver abajo)">% Promo (hermanos)</label>
                    <input type="number" step="0.01" min="0" max="100" name="porcentaje_promo" value="{{ old('porcentaje_promo', $alumno->porcentaje_promo) }}" class="sg-input sg-input--chico w-full mt-1">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-400">% Beca</label>
                    <input type="number" step="0.01" min="0" max="100" name="porcentaje_beca" value="{{ old('porcentaje_beca', $alumno->porcentaje_beca) }}" class="sg-input sg-input--chico w-full mt-1">
                </div>
                <div class="col-span-2 flex justify-end">
                    <button type="submit" class="sg-btn sg-btn--azul sg-btn--chico">Guardar beneficios</button>
                </div>
            </form>
        </div>
        <div class="sg-card p-5">
            <div class="flex items-center gap-3 mb-4">
                <span class="sg-chip sg-chip--chico sg-chip--amarillo"><x-sg.icono nombre="cap" /></span>
                <h3 class="font-display font-bold text-slate-800 text-sm">Situación académica</h3>
            </div>
            <dl class="text-sm space-y-2.5">
                <div class="flex justify-between"><dt class="text-slate-400">Fecha de ingreso</dt><dd class="text-slate-700 font-medium">{{ $inscripcion?->fecha_inscripcion ? \App\Support\FechaEsp::corta($inscripcion->fecha_inscripcion) : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-400">Año lectivo</dt><dd class="text-slate-700 font-medium">{{ $inscripcion?->anioLectivo?->anio ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-400">Año que cursa</dt><dd class="text-slate-700 font-medium">{{ $inscripcion?->anioCursada?->nombre_anio ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-400">Turno</dt><dd class="text-slate-700 font-medium">{{ $inscripcion?->turnoCursada?->nombre_turno ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-400">Condición</dt><dd class="text-slate-700 font-medium">{{ $inscripcion?->condicion?->nombre_condicion ?? '—' }}</dd></div>
                <div class="flex justify-between items-center"><dt class="text-slate-400">Estado</dt><dd>
                    <span @class(['sg-badge', 'sg-badge--verde' => $estadoInscNombre === 'Activo', 'sg-badge--rojo' => $estadoInscNombre === 'Baja'])>
                        {{ $estadoInscNombre ?? '—' }}
                    </span>
                </dd></div>
            </dl>
        </div>
        <div class="sg-card p-5">
            <div class="flex items-center gap-3 mb-4">
                <span class="sg-chip sg-chip--chico sg-chip--verde"><x-sg.icono nombre="banknotes" /></span>
                <h3 class="font-display font-bold text-slate-800 text-sm">Cuotas</h3>
            </div>
            <div class="grid grid-cols-3 gap-2 text-center">
                <div class="rounded-xl bg-emerald-50 py-3">
                    <p class="font-display text-2xl font-extrabold text-emerald-600" data-sg-contar>{{ $cuotasPagadas }}</p>
                    <p class="text-[11px] font-semibold text-emerald-700/80">Pagadas</p>
                </div>
                <div class="rounded-xl bg-amber-50 py-3">
                    <p class="font-display text-2xl font-extrabold text-amber-600" data-sg-contar>{{ $cuotasPendientes }}</p>
                    <p class="text-[11px] font-semibold text-amber-700/80">Pendientes</p>
                </div>
                <div class="rounded-xl bg-rose-50 py-3">
                    <p class="font-display text-2xl font-extrabold text-rose-600" data-sg-contar>{{ $cuotasVencidas }}</p>
                    <p class="text-[11px] font-semibold text-rose-700/80">Vencidas</p>
                </div>
            </div>
            @if ($alumno->cuotas->count() > 0)
                <div class="sg-progreso mt-4"><span style="width: {{ round($cuotasPagadas / max($alumno->cuotas->count(), 1) * 100) }}%"></span></div>
                <p class="text-xs text-slate-400 mt-2">{{ $cuotasPagadas }} de {{ $alumno->cuotas->count() }} cuotas abonadas</p>
            @endif
        </div>
    </div>

    <div class="sg-card sg-revelar p-5 mb-6">
        <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
            <div class="flex items-center gap-3">
                <span class="sg-chip sg-chip--chico sg-chip--verde"><x-sg.icono nombre="users" /></span>
                <div>
                    <h3 class="font-display font-bold text-slate-800 text-sm">Hermanos vinculados</h3>
                    <p class="text-xs text-slate-400">La promo del alumno se aplica solo mientras el hermano/a vinculado esté activo</p>
                </div>
            </div>
            @if ($alumno->porcentaje_promo > 0 && ! $alumno->tieneHermanoActivo())
                <span class="sg-badge sg-badge--amarillo">% promo cargado pero sin aplicar: no hay hermano activo vinculado</span>
            @endif
        </div>

        @if ($alumno->hermanos->isNotEmpty())
            <div class="grid sm:grid-cols-2 gap-2 mb-4">
                @foreach ($alumno->hermanos as $hermano)
                    @php $estadoHermano = $hermano->inscripcionesCarrera->first()?->estadoInscripcion?->nombre_estado; @endphp
                    <div class="flex items-center justify-between gap-3 rounded-2xl border-[1.5px] border-slate-100 px-4 py-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="sg-avatar">{{ mb_substr($hermano->nombre, 0, 1) }}{{ mb_substr($hermano->apellido, 0, 1) }}</span>
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-700 text-sm truncate">{{ $hermano->apellido }}, {{ $hermano->nombre }}</p>
                                <p class="text-xs text-slate-400">DNI {{ $hermano->dni }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            @if ($estadoHermano === 'Activo')
                                <span class="sg-badge sg-badge--verde">Activo</span>
                            @else
                                <span class="sg-badge sg-badge--rojo">{{ $estadoHermano ?? 'Sin inscripción' }}</span>
                            @endif
                            <form method="POST" action="{{ route('admin.alumnos.hermanos.destroy', [$alumno, $hermano]) }}" onsubmit="return confirm('¿Quitar el vínculo de hermano/a?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="h-8 w-8 grid place-items-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition-colors" title="Quitar vínculo"><x-sg.icono nombre="trash" class="h-4 w-4" /></button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-slate-400 mb-4">Todavía no tiene hermanos vinculados.</p>
        @endif

        <form method="GET" action="{{ route('admin.alumnos.show', $alumno) }}" class="grid sm:grid-cols-[1fr_auto] gap-3">
            <div class="sg-campo-icono">
                <x-sg.icono nombre="search" />
                <input type="text" name="q_hermano" value="{{ $busquedaHermano }}" placeholder="Buscar alumno por nombre, apellido o DNI..." maxlength="20"
                       oninput="this.value = this.value.replace(/[^A-Za-zÁÉÍÓÚÑÜáéíóúñü0-9\s]/g, '')"
                       class="sg-input w-full">
            </div>
            <button class="sg-btn sg-btn--azul"><x-sg.icono nombre="search" /> Buscar</button>
        </form>

        @if ($busquedaHermano)
            @if ($resultadosHermano->isNotEmpty())
                <div class="grid sm:grid-cols-2 gap-2 mt-3">
                    @foreach ($resultadosHermano as $candidato)
                        <div class="flex items-center justify-between gap-3 rounded-2xl border-[1.5px] border-slate-100 px-4 py-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="sg-avatar">{{ mb_substr($candidato->nombre, 0, 1) }}{{ mb_substr($candidato->apellido, 0, 1) }}</span>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-700 text-sm truncate">{{ $candidato->apellido }}, {{ $candidato->nombre }}</p>
                                    <p class="text-xs text-slate-400">DNI {{ $candidato->dni }} · {{ $candidato->inscripcionesCarrera->first()?->carrera?->nombre_carrera }}</p>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('admin.alumnos.hermanos.store', $alumno) }}">
                                @csrf
                                <input type="hidden" name="id_persona_hermano" value="{{ $candidato->id_persona }}">
                                <button type="submit" class="sg-btn sg-btn--verde sg-btn--chico shrink-0">Vincular</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="sg-aviso sg-aviso--info mt-3">No se encontraron alumnos que coincidan con "{{ $busquedaHermano }}".</p>
            @endif
        @endif
    </div>

    @php
        $cuotasOrdenadas = $alumno->cuotas->sortBy([['id_anio_lectivo', 'asc'], ['id_mes', 'asc']]);
        $cuotasAdeudadasCount = $cuotasOrdenadas->where('pagado', false)->count();
        $totalEstimadoAdeudado = $montoActualCuota ? $cuotasAdeudadasCount * (float) $montoActualCuota : null;
        $porcentajeDescuentoCuotas = $alumno->porcentajeDescuentoCuotas();
    @endphp

    <x-sg.tarjeta titulo="Historial de Cuotas" subtitulo="Todas las cuotas generadas para este alumno, pagadas y pendientes" icono="banknotes" tono="verde" class="mb-6" :cuerpo="false">
        @if ($cuotasAdeudadasCount > 0 && $montoActualCuota)
            <div class="mx-5 mt-4 flex items-center justify-between gap-3 rounded-2xl bg-gradient-to-r from-[#0B3D91] to-[#1565C0] text-white px-5 py-3">
                <span class="text-sm font-semibold text-blue-100">Adeuda {{ $cuotasAdeudadasCount }} cuota{{ $cuotasAdeudadasCount === 1 ? '' : 's' }} × $ {{ number_format($montoActualCuota, 0, ',', '.') }} (monto actual)</span>
                <span class="font-display font-extrabold text-xl text-[#FFD54F]">$ {{ number_format($totalEstimadoAdeudado, 0, ',', '.') }}</span>
            </div>
        @endif
        <div class="overflow-x-auto mt-4">
            <table class="sg-tabla">
                <thead>
                    <tr>
                        <th>Período</th>
                        <th>Concepto</th>
                        <th>Monto</th>
                        <th>Descuento</th>
                        <th>Recargo</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Fecha de pago</th>
                    </tr>
                </thead>
                <tbody class="sg-filas">
                    @forelse ($cuotasOrdenadas as $cuota)
                        <tr>
                            <td class="text-slate-500">{{ $cuota->mes->nombre_mes }} {{ $cuota->anioLectivo->anio }}</td>
                            <td class="text-slate-500">{{ $cuota->concepto ?: '—' }}</td>
                            <td class="font-medium text-slate-700">$ {{ number_format($cuota->monto, 0, ',', '.') }}</td>
                            <td>
                                @if ($cuota->montoDescuento($porcentajeDescuentoCuotas) > 0)
                                    <span class="sg-badge sg-badge--verde sg-badge--sin-punto">-$ {{ number_format($cuota->montoDescuento($porcentajeDescuentoCuotas), 0, ',', '.') }}</span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($cuota->recargo > 0)
                                    <span class="sg-badge sg-badge--amarillo sg-badge--sin-punto">+$ {{ number_format($cuota->recargo, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="font-display font-bold text-[#0B3D91]">$ {{ number_format($cuota->montoTotal($porcentajeDescuentoCuotas), 0, ',', '.') }}</td>
                            <td>
                                @if ($cuota->pagado)
                                    <span class="sg-badge sg-badge--verde">Pagada</span>
                                @elseif ($cuota->vencida)
                                    <span class="sg-badge sg-badge--rojo">Vencida</span>
                                @else
                                    <span class="sg-badge sg-badge--amarillo">Pendiente</span>
                                @endif
                            </td>
                            <td class="text-slate-500">{{ $cuota->fecha_pago ? \App\Support\FechaEsp::corta($cuota->fecha_pago) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-sg.vacio icono="banknotes">Todavía no se generó ninguna cuota para este alumno.</x-sg.vacio></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-sg.tarjeta>

    <x-sg.tarjeta titulo="Historial académico" subtitulo="Solo consulta — para cargar o corregir una materia, usá 'Cargar historial previo' desde Alumnos" icono="book" class="mb-6" :cuerpo="false">
        <div class="px-5 pt-4 pb-3 grid sm:grid-cols-2 gap-x-6 gap-y-1.5 text-sm border-b border-slate-100">
            <div class="flex justify-between gap-3"><dt class="text-slate-400">Carrera</dt><dd class="text-slate-700 font-medium text-right">{{ $inscripcion?->carrera?->nombre_carrera ?? '—' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-400">Resolución del plan de estudios</dt><dd class="text-slate-700 font-medium text-right">{{ $inscripcion?->carrera?->resolucion_ministerial ?? '—' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-400">DNI</dt><dd class="text-slate-700 font-medium text-right">{{ $alumno->dni }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-400">Año de ingreso a la carrera</dt><dd class="text-slate-700 font-medium text-right">{{ $anioIngreso ?? '—' }}</dd></div>
        </div>

        @if ($alumno->historialAlumno->isEmpty())
            <div class="p-5"><x-sg.vacio icono="book">Sin historial académico cargado.</x-sg.vacio></div>
        @else
            <div class="overflow-x-auto">
                <table class="sg-tabla">
                    <thead>
                        <tr>
                            <th>Materia</th>
                            <th>Condición</th>
                            <th class="sg-num">Nota</th>
                            <th>Fecha</th>
                            <th>Libro / Folio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($alumno->historialAlumno->sortBy('materia.nombre') as $historial)
                            @php
                                $condicionNombre = $historial->condicion->nombre_condicion ?? '—';
                                $aprobada = \App\Models\CondicionAlumno::esAprobada($condicionNombre);
                                $acta = $actasPorMateria->get($historial->id_materia);
                            @endphp
                            <tr>
                                <td>
                                    <span class="flex items-center gap-2.5 min-w-0">
                                        <span @class([
                                                'h-2 w-2 rounded-full shrink-0',
                                                'bg-emerald-500' => $aprobada,
                                                'bg-amber-400' => $condicionNombre === 'Regular' && ! $historial->regularidad_vencida,
                                                'bg-rose-500' => $historial->regularidad_vencida || $condicionNombre === 'Libre',
                                                'bg-slate-300' => $condicionNombre === 'Pendiente',
                                                'bg-sky-500' => $condicionNombre === 'Cursando',
                                            ])></span>
                                        <span class="font-semibold text-slate-700">{{ $historial->materia->nombre }}</span>
                                    </span>
                                </td>
                                <td>
                                    <span @class([
                                            'sg-badge',
                                            'sg-badge--verde' => $aprobada,
                                            'sg-badge--amarillo' => $condicionNombre === 'Regular' && ! $historial->regularidad_vencida,
                                            'sg-badge--rojo' => $historial->regularidad_vencida || $condicionNombre === 'Libre',
                                            'sg-badge--gris' => $condicionNombre === 'Pendiente',
                                            'sg-badge--azul' => $condicionNombre === 'Cursando',
                                        ])>
                                        {{ $historial->regularidad_vencida ? 'Venció' : $condicionNombre }}
                                    </span>
                                    @if ($condicionNombre === 'Regular' && $historial->fecha_limite_calculada)
                                        <p class="text-[11px] text-slate-400 mt-1">{{ $historial->regularidad_vencida ? 'venció el' : 'vence' }} {{ \App\Support\FechaEsp::corta($historial->fecha_limite_calculada) }}</p>
                                    @endif
                                    @if ($condicionNombre === 'Equivalencia' && ($historial->institucion_equivalencia || $historial->resolucion_equivalencia))
                                        <p class="text-[11px] text-slate-400 mt-1">
                                            {{ $historial->institucion_equivalencia ?: '—' }}
                                            @if ($historial->resolucion_equivalencia)
                                                · Res. {{ $historial->resolucion_equivalencia }}
                                            @endif
                                        </p>
                                    @endif
                                </td>
                                <td class="sg-num font-semibold text-slate-700">{{ $historial->nota_cursada ?? '—' }}</td>
                                <td class="text-slate-500">{{ $aprobada && $historial->fecha_ultima_modificacion ? \App\Support\FechaEsp::corta($historial->fecha_ultima_modificacion) : '—' }}</td>
                                <td class="text-slate-500">{{ $acta ? "{$acta->acta->libro}/{$acta->acta->folio}" : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($alumno->historialAlumno->isNotEmpty())
            <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between text-sm bg-slate-50/60">
                <span class="text-slate-500">{{ $materiasAprobadasTotal }} de {{ $totalMateriasPlan }} materias del plan aprobadas</span>
                <span class="font-display font-bold text-[#0B3D91]">{{ $porcentajeAvance }}% de avance</span>
            </div>
        @endif
    </x-sg.tarjeta>

    <x-sg.tarjeta titulo="Ficha de inscripción" subtitulo="Datos que completó el alumno desde su portal — solo consulta, la edición la hace el propio alumno" icono="document" tono="azul">
        @php $algunaRespuesta = $fichaRespuestas->isNotEmpty(); @endphp
        @if (! $algunaRespuesta)
            <x-sg.vacio icono="document">El alumno todavía no completó su ficha de inscripción.</x-sg.vacio>
        @else
            <div class="grid md:grid-cols-2 gap-x-8 gap-y-5">
                @foreach ($fichaSecciones as $seccion)
                    @continue($seccion->campos->isEmpty())
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-2">{{ $seccion->nombre }}</p>
                        <dl class="text-sm space-y-2 text-slate-500">
                            @foreach ($seccion->campos as $campo)
                                <div class="flex justify-between gap-4">
                                    <dt class="shrink-0">{{ $campo->etiqueta }}</dt>
                                    <dd class="text-slate-700 text-right">{{ $fichaRespuestas->get($campo->id_campo)?->valor ?? '—' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endforeach
            </div>
        @endif
    </x-sg.tarjeta>
@endsection
