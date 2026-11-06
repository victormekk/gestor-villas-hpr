@extends('layouts.pdf')
@use('App\Support\Formato')
@use('App\Support\PaginasPdf')

@php
    // anchos en % (suman 100). "Todos los cargos / creditos" agrega la columna del concepto: le quita
    // ancho al propietario y a la descripcion (los nombres de los creditos son cortos, "Abono / Pago")
    $anchoPropietario = $conColumnaConcepto ? 17 : 20;
    $anchoConcepto = $conColumnaConcepto ? ($esCargo ? 16 : 12) : 0;
    $anchoDescr = ($esCargo ? 48 : 38) + (20 - $anchoPropietario) - $anchoConcepto;
    $columnas = 6 + ($esCargo ? 0 : 1) + ($conColumnaConcepto ? 1 : 0);
    // una tabla por pagina: con miles de movimientos una sola tabla se quedaba sin memoria
    $paginas = PaginasPdf::partir($filas, 'landscape', array_filter([
        'villa' => 0.06,
        'propietario' => $anchoPropietario / 100,
        'concepto' => $anchoConcepto / 100,
        'descripcion' => $anchoDescr / 100,
        'forma_pago' => $esCargo ? 0 : 0.10,
    ]));
@endphp

@section('contenido')
    @foreach ($paginas as $pagina)
        <table @class(['datos', 'por-pagina', 'pagina-nueva' => ! $loop->first])>
            <thead>
                <tr>
                    <th style="width: 8%;">Fecha</th>
                    <th style="width: 9%;">Correlativo No.</th>
                    <th style="width: 6%;">Villa</th>
                    <th style="width: {{ $anchoPropietario }}%;">Propietario</th>
                    @if ($conColumnaConcepto)
                        <th style="width: {{ $anchoConcepto }}%;">Concepto</th>
                    @endif
                    <th style="width: {{ $anchoDescr }}%;">Descripción</th>
                    @unless ($esCargo)
                        <th style="width: 10%;">Forma de pago</th>
                    @endunless
                    <th class="der" style="width: 9%;">Importe</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pagina as $f)
                    <tr>
                        <td>{{ Formato::fecha($f['fecha']) }}</td>
                        <td>{{ $f['correlativo'] ?? '—' }}</td>
                        <td>{{ $f['villa'] }}</td>
                        <td>{{ $f['propietario'] }}</td>
                        @if ($conColumnaConcepto)
                            <td>{{ $f['concepto'] }}</td>
                        @endif
                        <td>{{ $f['descripcion'] }}</td>
                        @unless ($esCargo)
                            <td>{{ $f['forma_pago'] ?? '—' }}</td>
                        @endunless
                        <td class="der">{{ Formato::monto($f['importe']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $columnas }}" class="vacio">Sin movimientos {{ $conColumnaConcepto ? 'de este tipo' : 'de este concepto' }} en el rango</td></tr>
                @endforelse
            </tbody>
            @if ($loop->last && count($filas) > 0)
                <tfoot>
                    <tr class="total-grande">
                        <td colspan="{{ $columnas - 1 }}" class="etiqueta">TOTAL ({{ count($filas) }} {{ count($filas) === 1 ? 'movimiento' : 'movimientos' }})</td>
                        <td class="der gran-total">{{ Formato::monto($total) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    @endforeach
@endsection
