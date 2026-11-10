<?php

/*
 * Generacion de los PDF de reportes (estado de cuenta, saldos, antigüedad, por concepto, listados y
 * bitacora). Los recibos y notas de cargo siguen con dompdf: son de una pagina y ya salen rapido.
 */
return [

    /*
     * "chrome": Chrome/Chromium sin ventana (mucho mas rapido con reportes grandes: el estado de cuenta
     * de todas las villas en un año baja de ~2 minutos a segundos). Si no se encuentra Chrome o falla,
     * se usa dompdf automaticamente para no dejar al usuario sin su PDF.
     * "dompdf": siempre dompdf (el motor anterior).
     */
    'motor' => env('PDF_MOTOR', 'chrome'),

    /*
     * Ruta del ejecutable de Chrome/Chromium. Si esta vacia se busca en las rutas habituales de
     * Windows (Chrome o Edge) y Linux (chromium, google-chrome). En el Dockerfile es /usr/bin/chromium.
     */
    'chrome_path' => env('CHROME_PATH'),

    /* Tiempo maximo, en segundos, para que Chrome arme un PDF. */
    'timeout' => (int) env('PDF_TIMEOUT', 300),

];
