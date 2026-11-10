<?php

namespace App\Support;

use HeadlessChromium\BrowserFactory;
use Illuminate\Support\Str;

/**
 * Convierte el HTML de un reporte a PDF con Chrome/Chromium sin ventana.
 *
 * Es mucho mas rapido que dompdf con reportes largos (el estado de cuenta de todas las villas en un
 * año: ~6 s contra ~2 min) y usa mucha menos memoria de PHP. Respeta los margenes de `@page` del
 * layout `layouts.pdf` y dibuja el pie de pagina de cada hoja (ver PIE).
 */
class PdfChrome
{
    /** Carta: 8.5 x 11 pulgadas. */
    private const ANCHO_CARTA = 8.5;

    private const ALTO_CARTA = 11;

    /**
     * Pie de cada pagina: igual al que se dibujaba con dompdf -- "Hotel y Villas Palma Real" a la
     * izquierda y "Pagina X de Y" a la derecha, Helvetica 8 pt en gris neutro, alineados con los
     * margenes laterales de 3 cm del layout. Chrome lo coloca dentro del margen inferior de la pagina.
     * Las plantillas de Chrome no heredan estilos: todo va en linea y el tamaño de letra es obligatorio.
     */
    private const PIE = <<<'HTML'
        <div style="width: 100%; box-sizing: border-box; padding: 0 3cm 0.55cm 3cm; display: flex; justify-content: space-between; font-family: Helvetica, Arial, sans-serif; font-size: 8pt; color: #454545;">
            <span>Hotel y Villas Palma Real</span>
            <span>Página <span class="pageNumber"></span> de <span class="totalPages"></span></span>
        </div>
        HTML;

    /** Rutas habituales del navegador cuando no se configura CHROME_PATH. */
    private const RUTAS = [
        '/usr/bin/chromium',
        '/usr/bin/chromium-browser',
        '/usr/bin/google-chrome',
        '/usr/bin/google-chrome-stable',
        'C:\Program Files\Google\Chrome\Application\chrome.exe',
        'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
        'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
        'C:\Program Files\Microsoft\Edge\Application\msedge.exe',
    ];

    /** Si se debe usar Chrome: el motor configurado es "chrome" y el ejecutable existe. */
    public static function disponible(): bool
    {
        return config('pdf.motor') === 'chrome' && self::ejecutable() !== null;
    }

    public static function ejecutable(): ?string
    {
        $configurada = config('pdf.chrome_path');
        if ($configurada) {
            return is_file($configurada) ? $configurada : null;
        }

        foreach (self::RUTAS as $ruta) {
            if (is_file($ruta)) {
                return $ruta;
            }
        }

        return null;
    }

    /** Contenido binario del PDF. Lanza una excepcion si Chrome no arranca o tarda demasiado. */
    public static function generar(string $html, string $orientacion = 'portrait'): string
    {
        $timeoutMs = config('pdf.timeout') * 1000;

        // el HTML va a un archivo y no por el canal de Chrome: un estado de cuenta de todas las villas
        // pesa varios MB y asi no hay limite de tamaño del mensaje
        $carpeta = storage_path('app/pdf-temporal');
        if (! is_dir($carpeta)) {
            mkdir($carpeta, 0775, true);
        }
        $archivo = $carpeta.DIRECTORY_SEPARATOR.Str::uuid().'.html';
        file_put_contents($archivo, $html);

        $navegador = (new BrowserFactory(self::ejecutable()))->createBrowser([
            'headless' => true,
            // en Docker corre como root, y Chrome no arranca con el sandbox siendo root
            'noSandbox' => true,
            'startupTimeout' => 30,
            'sendSyncDefaultTimeout' => $timeoutMs,
            'customFlags' => [
                // /dev/shm de los contenedores es chico y Chrome se cae con paginas grandes
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--font-render-hinting=none',
            ],
        ]);

        try {
            $pagina = $navegador->createPage();
            $pagina->navigate(self::urlDeArchivo($archivo))->waitForNavigation('load', $timeoutMs);

            $horizontal = $orientacion === 'landscape';

            return $pagina->pdf([
                'paperWidth' => $horizontal ? self::ALTO_CARTA : self::ANCHO_CARTA,
                'paperHeight' => $horizontal ? self::ANCHO_CARTA : self::ALTO_CARTA,
                'landscape' => false, // el tamaño ya va girado arriba
                // margenes de @page del layout (2.54 cm arriba/abajo, 3 cm a los lados)
                'preferCSSPageSize' => true,
                'printBackground' => true,
                'displayHeaderFooter' => true,
                'headerTemplate' => '<span></span>',
                'footerTemplate' => self::PIE,
            ])->getRawBinary($timeoutMs);
        } finally {
            $navegador->close();
            @unlink($archivo);
        }
    }

    /** "C:\a b\x.html" -> "file:///C:/a%20b/x.html"; "/app/x.html" -> "file:///app/x.html". */
    private static function urlDeArchivo(string $ruta): string
    {
        $partes = explode('/', str_replace('\\', '/', $ruta));
        $partes = array_map(
            // la letra de unidad de Windows ("C:") no se codifica
            fn (string $parte, int $i) => $i === 0 && preg_match('/^[A-Za-z]:$/', $parte) ? $parte : rawurlencode($parte),
            $partes,
            array_keys($partes),
        );

        return 'file:///'.ltrim(implode('/', $partes), '/');
    }
}
