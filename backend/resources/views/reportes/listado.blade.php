{{-- Listados de personas (propietarios, encargados, historial de villas): columnas definidas en ReporteController::datosListado. --}}
@extends('layouts.pdf')
@use('App\Support\Formato')
@use('App\Support\PaginasPdf')

@php
    // anchos fijos (iguales en todas las hojas) en proporcion a los del Excel; la villa y las fechas con
    // un minimo para que "TH-13" o "06/11/2026" no se partan en dos renglones
    $minimos = array_map(fn ($c) => $c['clave'] === 'villa' ? 0.06 : (! empty($c['fecha']) ? 0.09 : 0), $columnas);
    $proporcion = array_map(fn ($c) => $c['ancho'], $columnas);
    $libre = 1 - array_sum($minimos);
    $resto = array_sum(array_map(fn ($p, $m) => $m ? 0 : $p, $proporcion, $minimos));
    $anchos = array_map(fn ($p, $m) => $m ?: $p / $resto * $libre, $proporcion, $minimos);
    $fechas = array_filter($columnas, fn ($c) => ! empty($c['fecha']));

    // una tabla por pagina (ver App\Support\PaginasPdf), midiendo el texto ya formateado (fechas d/m/Y)
    $filasPdf = collect($filas)->map(fn ($f) => array_merge($f, array_combine(
        array_column($fechas, 'clave'),
        array_map(fn ($c) => Formato::fecha($f[$c['clave']]), $fechas),
    ) ?: []));
    $paginas = PaginasPdf::partir($filasPdf, 'landscape', array_combine(array_column($columnas, 'clave'), $anchos));
@endphp

@section('contenido')
    @foreach ($paginas as $pagina)
        <table @class(['datos', 'por-pagina', 'pagina-nueva' => ! $loop->first])>
            <thead>
                <tr>
                    @foreach ($columnas as $i => $c)
                        <th style="width: {{ round($anchos[$i] * 100, 2) }}%;">{{ $c['titulo'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($pagina as $f)
                    <tr>
                        @foreach ($columnas as $c)
                            <td>{{ $f[$c['clave']] }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($columnas) }}" class="vacio">{{ $vacio }}</td></tr>
                @endforelse
            </tbody>
            @if ($loop->last && count($filas) > 0)
                <tfoot>
                    <tr class="total">
                        <td colspan="{{ count($columnas) }}">{{ $etiquetaTotal }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    @endforeach
@endsection
