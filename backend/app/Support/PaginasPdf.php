<?php

namespace App\Support;

use Dompdf\Dompdf;
use Dompdf\FontMetrics;
use Illuminate\Support\Collection;

/**
 * Reparte las filas de un reporte PDF en paginas, para dibujar una tabla chica por pagina en vez de
 * una sola tabla gigante: dompdf calcula cada tabla completa en memoria y con unos miles de filas se
 * pasaba de 1 GB. Con una tabla por pagina el PDF se ve igual (encabezado de columnas arriba de cada
 * hoja y el total al final) y la memoria baja varias veces.
 *
 * Cuantas filas caben se calcula por renglones: una fila con texto largo se parte en dos o tres
 * renglones y ocupa mas (se mide con las mismas medidas de letra que usa dompdf). Si la estimacion se queda corta no se pierde nada: dompdf parte esa tabla y
 * repite el encabezado en la hoja siguiente, como antes.
 */
class PaginasPdf
{
    /**
     * Filas de un renglon que caben en la primera pagina (lleva la cabecera del reporte) y en las demas.
     * Medido con layouts.pdf (carta, margenes de 2.54 cm y 3 cm, letra de 10px), con una fila de holgura.
     */
    private const CAPACIDAD = ['landscape' => [19, 24], 'portrait' => [29, 34]];

    /** Lo que ocupa cada renglon extra de una fila, en filas de un renglon (medido: ~0.56). */
    private const RENGLON_EXTRA = 0.58;

    /** Ancho util de la hoja carta sin los margenes de 3 cm, en pt. */
    private const ANCHO_UTIL = ['landscape' => 622, 'portrait' => 442];

    /** Letra de las celdas: 10px de layouts.pdf a 96 dpi = 7.5 pt. */
    private const TAMANO_LETRA = 7.5;

    /** Relleno izquierdo + derecho de cada celda (7px por lado), en pt. */
    private const RELLENO_CELDA = 10.5;

    /** Medidas de las letras de Helvetica, las mismas con las que dompdf parte el texto en renglones. */
    private static ?FontMetrics $metricas = null;

    private static string $fuente;

    /**
     * @param  iterable<array<string, mixed>>  $filas
     * @param  array<string, float>  $columnas  clave de la fila => fraccion del ancho de la tabla (0 a 1)
     *                                          de cada columna de texto que puede partirse en renglones
     * @param  float  $reservaFinal  espacio (en filas) que se deja en la ultima pagina para la fila del total
     * @return list<Collection>
     */
    public static function partir(iterable $filas, string $orientacion, array $columnas, float $reservaFinal = 1.5): array
    {
        [$primera, $siguientes] = self::CAPACIDAD[$orientacion];
        $paginas = [];
        $actual = [];
        $usado = 0.0;
        $capacidad = $primera;

        foreach ($filas as $fila) {
            $alto = self::alto($fila, $orientacion, $columnas);
            if ($actual && $usado + $alto > $capacidad) {
                $paginas[] = collect($actual);
                [$actual, $usado, $capacidad] = [[], 0.0, $siguientes];
            }
            $actual[] = $fila;
            $usado += $alto;
        }

        // el total no cabe debajo de la ultima fila: se pasan las dos ultimas a una hoja nueva, para
        // que el total nunca quede solo
        if ($paginas && $usado + $reservaFinal > $capacidad && count($actual) > 2) {
            $paginas[] = collect(array_slice($actual, 0, -2));
            $actual = array_slice($actual, -2);
        }
        if ($actual || ! $paginas) {
            $paginas[] = collect($actual);
        }

        return $paginas;
    }

    /** Fraccion del ancho de la tabla que ocupa una columna de ancho fijo en px. */
    public static function fraccion(string $orientacion, float $px): float
    {
        return $px * 0.75 / self::ANCHO_UTIL[$orientacion];
    }

    /** @param array<string, float> $columnas */
    private static function alto(array $fila, string $orientacion, array $columnas): float
    {
        $renglones = 1;
        foreach ($columnas as $clave => $fraccion) {
            $ancho = self::ANCHO_UTIL[$orientacion] * $fraccion - self::RELLENO_CELDA;
            $renglones = max($renglones, self::renglones((string) ($fila[$clave] ?? ''), $ancho));
        }

        return 1 + ($renglones - 1) * self::RENGLON_EXTRA;
    }

    /** Renglones que ocupa un texto en una celda de este ancho (en pt), partiendolo por palabras como dompdf. */
    private static function renglones(string $texto, float $ancho): int
    {
        if ($texto === '') {
            return 1;
        }
        if (! self::$metricas) {
            self::$metricas = (new Dompdf)->getFontMetrics();
            self::$fuente = self::$metricas->getFont('helvetica');
        }
        $medir = fn (string $t) => self::$metricas->getTextWidth($t, self::$fuente, self::TAMANO_LETRA);

        $renglones = 1;
        $usado = 0.0;
        $espacio = $medir(' ');
        foreach (preg_split('/\s+/u', trim($texto)) as $palabra) {
            $w = $medir($palabra);
            if ($usado > 0 && $usado + $espacio + $w > $ancho) {
                $renglones++;
                $usado = 0.0;
            }
            $usado += ($usado > 0 ? $espacio : 0) + $w;
            // una palabra mas ancha que la celda se parte en varios renglones
            while ($usado > $ancho) {
                $renglones++;
                $usado -= $ancho;
            }
        }

        return $renglones;
    }
}
