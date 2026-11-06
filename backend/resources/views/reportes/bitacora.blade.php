@extends('layouts.pdf')
@use('App\Support\PaginasPdf')

@php
    // anchos en % (equivalen a 84px, 78px y 118px): con table-layout fixed dompdf no respeta los px si
    // hay una columna sin ancho, y reparte parejo
    $ancho = fn (float $px) => round(PaginasPdf::fraccion('portrait', $px) * 100, 2);
    $anchoDescr = 100 - $ancho(84) - $ancho(78) - $ancho(118);
    // una tabla por pagina (ver App\Support\PaginasPdf)
    $paginas = PaginasPdf::partir($registros, 'portrait', [
        'usuario' => $ancho(78) / 100,
        'cambio' => $ancho(118) / 100,
        'descripcion' => $anchoDescr / 100,
    ]);
@endphp

@section('contenido')
    @foreach ($paginas as $pagina)
        <table @class(['datos', 'por-pagina', 'pagina-nueva' => ! $loop->first])>
            <thead>
                <tr>
                    <th style="width: {{ $ancho(84) }}%;">Fecha</th>
                    <th style="width: {{ $ancho(78) }}%;">Usuario</th>
                    <th style="width: {{ $ancho(118) }}%;">Cambio</th>
                    <th style="width: {{ $anchoDescr }}%;">Descripción</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pagina as $r)
                    <tr>
                        <td>{{ $r['fecha']->format('d/m/Y H:i') }}</td>
                        <td>{{ $r['usuario'] }}</td>
                        <td>{{ $r['cambio'] }}</td>
                        <td>{{ $r['descripcion'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="vacio">Sin registros para estos filtros</td></tr>
                @endforelse
            </tbody>
            @if ($loop->last && count($registros) > 0)
                <tfoot>
                    <tr class="total">
                        <td colspan="4">Total de registros: {{ count($registros) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    @endforeach
@endsection
