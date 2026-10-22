<?php
/**
 * Lee el archivo .env de la raíz y deja cada variable disponible con env('NOMBRE').
 * Formato: NOMBRE=valor (las líneas que empiezan con # se ignoran).
 */
function env(string $clave, $porDefecto = null) {
    static $vars = null;
    if ($vars === null) {
        $vars = [];
        $archivo = __DIR__ . '/../../.env';
        if (is_readable($archivo)) {
            foreach (file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
                $linea = trim($linea);
                if ($linea === '' || $linea[0] === '#' || !str_contains($linea, '=')) continue;
                [$k, $v] = explode('=', $linea, 2);
                $vars[trim($k)] = trim(trim($v), "\"'");
            }
        }
    }
    $valor = $vars[$clave] ?? getenv($clave);
    return ($valor === false || $valor === '') ? $porDefecto : $valor;
}