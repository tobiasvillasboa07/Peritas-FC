<?php
/**
 * Partidos y sectores de entradas definidos en assets/data/partidos.json.
 * - Los sectores (nombre y precio) se leen del JSON.
 * - Los partidos del JSON se copian a la tabla matches cada vez que el archivo cambia
 *   (los que ya están no se repiten; los que carga el admin desde el panel se mantienen).
 */

const ARCHIVO_PARTIDOS = __DIR__ . '/../../assets/data/partidos.json';

// Si el JSON falta o tiene un error, se usan estos sectores
const SECTORES_POR_DEFECTO = [
    'popular_norte' => ['Tribuna Popular Norte', 8000, true],
    'popular_sur'   => ['Tribuna Popular Sur', 8000, true],
    'platea_alta'   => ['Platea Alta Lateral', 15000, false],
    'platea_baja'   => ['Platea Baja Preferencial', 22500, false],
];

/** Lee partidos.json (una sola vez por página). */
function datos_partidos(): array {
    static $datos = null;
    if ($datos === null) {
        $json = is_readable(ARCHIVO_PARTIDOS) ? json_decode(file_get_contents(ARCHIVO_PARTIDOS), true) : null;
        if (!is_array($json) && is_readable(ARCHIVO_PARTIDOS)) error_log('partidos.json tiene un error de formato');
        $datos = is_array($json) ? $json : [];
    }
    return $datos;
}

/** Sectores con el formato clave => [nombre, precio, es_popular]. */
function sectores_desde_json(): array {
    $sectores = [];
    foreach (datos_partidos()['sectores'] ?? [] as $s) {
        if (empty($s['clave']) || empty($s['nombre']) || !isset($s['precio'])) continue;
        $sectores[$s['clave']] = [$s['nombre'], $s['precio'] + 0, !empty($s['popular'])];
    }
    return $sectores ?: SECTORES_POR_DEFECTO;
}

/** Copia a la base los partidos del JSON, solo cuando el archivo cambió. */
function sincronizar_partidos(PDO $pdo): void {
    if (!is_readable(ARCHIVO_PARTIDOS)) return;
    $huella = md5_file(ARCHIVO_PARTIDOS);

    $pdo->exec('CREATE TABLE IF NOT EXISTS ajustes (clave TEXT PRIMARY KEY, valor TEXT)');
    $st = $pdo->prepare("SELECT valor FROM ajustes WHERE clave = 'partidos_json'");
    $st->execute();
    if ($st->fetchColumn() === $huella) return;   // ya está sincronizado

    $ins = $pdo->prepare('INSERT OR IGNORE INTO matches (away_team, tournament, stadium, match_date, capacity) VALUES (?, ?, ?, ?, ?)');
    foreach (datos_partidos()['partidos'] ?? [] as $p) {
        $fecha = strtotime($p['fecha'] ?? '');
        if (empty($p['rival']) || !$fecha) continue;
        $ins->execute([
            mb_strtoupper(trim($p['rival'])),
            mb_strtoupper(trim($p['torneo'] ?? 'LIGA DE PRIMERA DIVISIÓN')),
            $p['estadio'] ?? 'Estadio Estrella Azul',
            date('Y-m-d H:i:s', $fecha),
            max(1, (int)($p['cupo'] ?? 500)),
        ]);
    }
    $pdo->prepare("INSERT OR REPLACE INTO ajustes (clave, valor) VALUES ('partidos_json', ?)")->execute([$huella]);
}


/**
 * Foto de fondo para un partido contra $rival.
 * Busca en assets/img/partidos una foto con el nombre del rival (por ejemplo "racing-digital.jpg").
 * Si no hay, usa "general.jpg". Si tampoco hay, devuelve null y queda el fondo de cancha de siempre.
 */
function foto_partido(string $rival): ?string {
    static $fotos = null;
    if ($fotos === null) {
        $fotos = [];
        foreach (glob(__DIR__ . '/../../assets/img/partidos/*') ?: [] as $archivo) {
            if (preg_match('/\.(jpe?g|png|webp)$/i', $archivo)) {
                $fotos[nombre_de_archivo(pathinfo($archivo, PATHINFO_FILENAME))] = '/assets/img/partidos/' . rawurlencode(basename($archivo));
            }
        }
    }
    return $fotos[nombre_de_archivo($rival)] ?? $fotos['general'] ?? null;
}

/** "Unión Tecnológica" -> "union-tecnologica": minúsculas, sin acentos y con guiones. */
function nombre_de_archivo(string $texto): string {
    $texto = strtr(mb_strtolower(trim($texto)), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    return trim(preg_replace('/[^a-z0-9]+/', '-', $texto), '-');
}
