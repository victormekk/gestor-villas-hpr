@extends('layouts.pdf')
@use('App\Support\Formato')
@use('App\Support\PaginasPdf')

@php
    // anchos en % (equivalen a 70px y 140px): con table-layout fixed dompdf no respeta los px si hay
    // una columna sin ancho, y reparte parejo
    $ancho = fn (float $px) => round(PaginasPdf::fraccion('portrait', $px) * 100, 2);
    $anchoPropietario = 100 - $ancho(70) - $ancho(140);
    // una tabla por pagina (ver App\Support\PaginasPdf)
    $paginas = PaginasPdf::partir($saldos, 'portrait', ['propietario' => $anchoPropietario / 100]);
@endphp

@section('contenido')
    @foreach ($paginas as $pagina)
        <table @class(['datos', 'por-pagina', 'pagina-nueva' => ! $loop->first])>
            <thead>
                <tr>
                    <th style="width: {{ $ancho(70) }}%;">Villa</th>
                    <th style="width: {{ $anchoPropietario }}%;">Propietario</th>
                    <th class="der" style="width: {{ $ancho(140) }}%;">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pagina as $s)
                    <tr>
                        <td>{{ $s['villa'] }}</td>
                        <td>{{ $s['propietario'] }}</td>
                        <td @class(['der', 'deuda' => $s['saldo'] > 0, 'favor' => $s['saldo'] < 0])>{{ Formato::monto($s['saldo']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="vacio">Sin resultados</td></tr>
                @endforelse
            </tbody>
            @if ($loop->last && count($saldos) > 0)
                <tfoot>
                    <tr class="total-grande">
                        <td colspan="2" class="etiqueta">{{ mb_strtoupper($etiquetaTotal) }}</td>
                        <td class="der gran-total">{{ Formato::monto($total) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    @endforeach
@endsection
