<?php
/**
 * Router del servidor de PHP (php -S). Cumple el rol del .htaccess:
 * el servidor de PHP no lee el .htaccess, así que acá bloqueamos los archivos privados
 * (la base de datos, el .env y los archivos de configuración) para que no se puedan descargar.
 */
$ruta = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

$privado = '#(^|/)\.'                                   // archivos y carpetas ocultos: .env, .git, .htaccess
         . '|\.(sqlite|sqlite3|db)(-wal|-shm)?$'         // la base de datos
         . '|\.(sql|sh|yml|yaml|log)$'                   // schema.sql, run.sh, litestream.yml
         . '|(^|/)Dockerfile$#i';

if (preg_match($privado, $ruta)) {
    http_response_code(404);
    exit('No encontrado');
}

return false;   // cualquier otra cosa: el servidor sigue normal (muestra el archivo o ejecuta el .php)
