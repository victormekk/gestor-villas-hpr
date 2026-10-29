{{-- Listados de personas (propietarios, encargados, historial de villas): columnas definidas en ReporteController::datosListado. --}}
@extends('layouts.pdf')
@use('App\Support\Formato')

@section('contenido')
    <table class="datos">
        <thead>
            <tr>
                @foreach ($columnas as $c)
                    <th @if ($c['clave'] === 'villa') style="width: 40px;" @elseif (! empty($c['fecha'])) style="width: 62px;" @endif>{{ $c['titulo'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($filas as $f)
                <tr @class(['zebra' => $loop->even])>
                    @foreach ($columnas as $c)
                        <td>{{ ! empty($c['fecha']) ? Formato::fecha($f[$c['clave']]) : $f[$c['clave']] }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($columnas) }}" class="vacio">{{ $vacio }}</td></tr>
            @endforelse
        </tbody>
        @if (count($filas) > 0)
            <tfoot>
                <tr class="total">
                    <td colspan="{{ count($columnas) }}">{{ $etiquetaTotal }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
@endsection
