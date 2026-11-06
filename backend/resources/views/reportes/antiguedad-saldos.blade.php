@extends('layouts.pdf')
@use('App\Support\Formato')
@use('App\Support\PaginasPdf')

@section('estilos')
        /* porcentajes del mismo tamaño que la fila de SALDOS de arriba */
        table.datos tr.porcentajes td { padding: 7px 7px; font-weight: bold; font-size: 12px; }
        table.datos tr.porcentajes td.etiqueta { text-transform: uppercase; letter-spacing: 1px; }
@endsection

@section('contenido')
    @php
        $buckets = ['d90mas' => '+90 días', 'd90' => '90 días', 'd60' => '60 días', 'd30' => '30 días'];
        // anchos en % (equivalen a 50px, 96px y 116px): con table-layout fixed dompdf no respeta los px
        // si hay una columna sin ancho, y reparte parejo
        $ancho = fn (float $px) => round(PaginasPdf::fraccion('landscape', $px) * 100, 2);
        $anchoPropietario = 100 - $ancho(50) - 4 * $ancho(96) - $ancho(116);
        // una tabla por pagina (ver App\Support\PaginasPdf); al final van dos filas: saldos y porcentajes
        $paginas = PaginasPdf::partir($antiguedad['filas'], 'landscape', ['propietario' => $anchoPropietario / 100], 3);
    @endphp

    @foreach ($paginas as $pagina)
        <table @class(['datos', 'por-pagina', 'pagina-nueva' => ! $loop->first])>
            <thead>
                <tr>
                    <th style="width: {{ $ancho(50) }}%;">Villa</th>
                    <th style="width: {{ $anchoPropietario }}%;">Propietario</th>
                    @foreach ($buckets as $etiqueta)
                        <th class="der" style="width: {{ $ancho(96) }}%;">{{ $etiqueta }}</th>
                    @endforeach
                    <th class="der" style="width: {{ $ancho(116) }}%;">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pagina as $f)
                    <tr>
                        <td>{{ $f['villa'] }}</td>
                        <td>{{ $f['propietario'] }}</td>
                        @foreach ($buckets as $clave => $etiqueta)
                            <td class="der">{{ $f['bucket'] === $clave ? Formato::monto($f['saldo']) : '' }}</td>
                        @endforeach
                        <td class="der"><strong>{{ Formato::monto($f['saldo']) }}</strong></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="vacio">Ninguna villa con saldo pendiente</td></tr>
                @endforelse
            </tbody>
            @if ($loop->last && count($antiguedad['filas']) > 0)
                <tfoot>
                    <tr class="total-grande">
                        <td colspan="2" class="etiqueta">SALDOS</td>
                        @foreach ($buckets as $clave => $etiqueta)
                            <td class="der">{{ Formato::monto($antiguedad['totales'][$clave]) }}</td>
                        @endforeach
                        <td class="der gran-total">{{ Formato::monto($antiguedad['total_saldo']) }}</td>
                    </tr>
                    <tr class="porcentajes">
                        <td colspan="2" class="etiqueta">PORCENTAJES</td>
                        @foreach ($buckets as $clave => $etiqueta)
                            <td class="der">{{ $antiguedad['porcentajes'][$clave] }}%</td>
                        @endforeach
                        <td class="der">100%</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    @endforeach
@endsection
