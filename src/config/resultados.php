<?php
/**
 * Resultados de los partidos y tabla de posiciones.
 * - La tabla resultados guarda cada partido jugado del torneo (de cualquier equipo).
 * - La primera vez (y cada vez que cambia) se cargan los de assets/data/resultados.json.
 * - El admin agrega o borra resultados desde src/views/admin/resultados.php.
 * - La tabla de posiciones se calcula sola: 3 puntos por ganar, 1 por empatar.
 */

const ARCHIVO_RESULTADOS = __DIR__ . '/../../assets/data/resultados.json';
const NOMBRE_CLUB = 'PERITAS FC';

function preparar_resultados(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS resultados (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        torneo TEXT NOT NULL,
        fecha DATE NOT NULL,
        local TEXT NOT NULL,
        visitante TEXT NOT NULL,
        goles_local INTEGER NOT NULL CHECK (goles_local >= 0),
        goles_visitante INTEGER NOT NULL CHECK (goles_visitante >= 0),
        CHECK (local <> visitante),
        UNIQUE (torneo, fecha, local, visitante)
    )");

    // Copia los resultados del JSON solo cuando el archivo cambió (igual que partidos.json)
    if (!is_readable(ARCHIVO_RESULTADOS)) return;
    $huella = md5_file(ARCHIVO_RESULTADOS);
    $pdo->exec('CREATE TABLE IF NOT EXISTS ajustes (clave TEXT PRIMARY KEY, valor TEXT)');
    $st = $pdo->prepare("SELECT valor FROM ajustes WHERE clave = 'resultados_json'");
    $st->execute();
    if ($st->fetchColumn() === $huella) return;

    $lista = json_decode(file_get_contents(ARCHIVO_RESULTADOS), true);
    if (!is_array($lista)) { error_log('resultados.json tiene un error de formato'); return; }
    foreach ($lista as $r) {
        try { guardar_resultado($pdo, $r['torneo'] ?? '', $r['fecha'] ?? '', $r['local'] ?? '', $r['visitante'] ?? '', $r['goles_local'] ?? -1, $r['goles_visitante'] ?? -1); }
        catch (InvalidArgumentException $e) { error_log('resultados.json: ' . $e->getMessage()); }
    }
    $pdo->prepare("INSERT OR REPLACE INTO ajustes (clave, valor) VALUES ('resultados_json', ?)")->execute([$huella]);
}

/** Valida y guarda un resultado (si ya existe ese partido, no lo repite). */
function guardar_resultado(PDO $pdo, string $torneo, string $fecha, string $local, string $visitante, $golesLocal, $golesVisitante): void {
    $torneo = mb_strtoupper(trim($torneo));
    $local = mb_strtoupper(trim($local));
    $visitante = mb_strtoupper(trim($visitante));
    $dia = strtotime($fecha);
    if ($torneo === '' || $local === '' || $visitante === '' || !$dia) throw new InvalidArgumentException('Completá torneo, fecha y los dos equipos.');
    if ($local === $visitante) throw new InvalidArgumentException('El local y el visitante tienen que ser distintos.');
    if (!is_numeric($golesLocal) || !is_numeric($golesVisitante) || $golesLocal < 0 || $golesVisitante < 0) throw new InvalidArgumentException('Los goles tienen que ser números de 0 para arriba.');
    $pdo->prepare('INSERT OR IGNORE INTO resultados (torneo, fecha, local, visitante, goles_local, goles_visitante) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$torneo, date('Y-m-d', $dia), $local, $visitante, (int)$golesLocal, (int)$golesVisitante]);
}

/** Los últimos resultados (los más nuevos primero). */
function ultimos_resultados(PDO $pdo, int $limite = 10): array {
    $st = $pdo->prepare('SELECT * FROM resultados ORDER BY fecha DESC, id DESC LIMIT ?');
    $st->execute([$limite]);
    return $st->fetchAll();
}

/** Torneos que tienen resultados cargados. */
function torneos_con_resultados(PDO $pdo): array {
    return $pdo->query('SELECT DISTINCT torneo FROM resultados ORDER BY torneo')->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Tabla de posiciones de un torneo.
 * Orden: puntos, diferencia de gol, goles a favor y nombre.
 */
function tabla_posiciones(PDO $pdo, string $torneo): array {
    $st = $pdo->prepare('SELECT * FROM resultados WHERE torneo = ?');
    $st->execute([$torneo]);
    $tabla = [];
    $fila = fn() => ['pj' => 0, 'pg' => 0, 'pe' => 0, 'pp' => 0, 'gf' => 0, 'gc' => 0, 'pts' => 0];
    foreach ($st->fetchAll() as $r) {
        foreach ([[$r['local'], $r['goles_local'], $r['goles_visitante']], [$r['visitante'], $r['goles_visitante'], $r['goles_local']]] as [$equipo, $af, $en]) {
            $tabla[$equipo] ??= $fila();
            $t = &$tabla[$equipo];
            $t['pj']++; $t['gf'] += $af; $t['gc'] += $en;
            if ($af > $en) { $t['pg']++; $t['pts'] += 3; }
            elseif ($af == $en) { $t['pe']++; $t['pts'] += 1; }
            else { $t['pp']++; }
            unset($t);
        }
    }
    $lista = [];
    foreach ($tabla as $equipo => $t) $lista[] = ['equipo' => $equipo, 'dg' => $t['gf'] - $t['gc']] + $t;
    usort($lista, fn($a, $b) => [$b['pts'], $b['dg'], $b['gf'], $a['equipo']] <=> [$a['pts'], $a['dg'], $a['gf'], $b['equipo']]);
    return $lista;
}