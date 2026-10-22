<?php
/**
 * Integración con Mercado Pago (Checkout Pro) usando cURL, sin librerías externas.
 *
 * Flujo:
 *  1. crear_compra() guarda un pago "pendiente" y le pide a Mercado Pago una preferencia.
 *  2. El usuario paga en la página de Mercado Pago (init_point).
 *  3. Mercado Pago lo devuelve a controllers/pagos/retorno.php (y avisa a webhook.php).
 *  4. confirmar_pago() consulta el pago en la API y, si está aprobado, crea la entrada.
 *
 * Necesita en el .env:  MP_ACCESS_TOKEN=TEST-xxxx   (y opcional APP_URL=https://tu-url)
 */
require_once __DIR__ . '/env.php';

// sector => [nombre, precio, es_popular]
const SECTORES = [
    'popular_norte' => ['Tribuna Popular Norte', 8000, true],
    'popular_sur'   => ['Tribuna Popular Sur', 8000, true],
    'platea_alta'   => ['Platea Alta Lateral', 15000, false],
    'platea_baja'   => ['Platea Baja Preferencial', 22500, false],
];

// Tabla de pagos (se crea sola si la base ya existía antes de este cambio)
$pdo->exec("CREATE TABLE IF NOT EXISTS pagos (
    id TEXT PRIMARY KEY,
    user_id TEXT NOT NULL,
    match_id TEXT NOT NULL,
    sector TEXT NOT NULL,
    monto REAL NOT NULL CHECK (monto > 0),
    estado TEXT NOT NULL DEFAULT 'pendiente' CHECK (estado IN ('pendiente', 'aprobado', 'rechazado')),
    preference_id TEXT NULL,
    mp_payment_id TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE
)");

// Pagos de cuotas de socio (external_reference empieza con "cuota-")
$pdo->exec("CREATE TABLE IF NOT EXISTS pagos_cuotas (
    id TEXT PRIMARY KEY,
    cuota_id INTEGER NOT NULL,
    user_id TEXT NOT NULL,
    monto REAL NOT NULL CHECK (monto > 0),
    estado TEXT NOT NULL DEFAULT 'pendiente' CHECK (estado IN ('pendiente', 'aprobado', 'rechazado')),
    mp_payment_id TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cuota_id) REFERENCES cuotas(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
)");

/** Crea la cuota del mes actual como pendiente si todavía no existe (no se repite gracias al UNIQUE). */
function generar_cuota_mes(PDO $pdo, string $userId): void {
    $st = $pdo->prepare('SELECT c.cuota_mensual FROM users u JOIN categorias c ON c.nombre = u.categoria WHERE u.id = ?');
    $st->execute([$userId]);
    $monto = (float)$st->fetchColumn();
    if ($monto > 0) {
        $pdo->prepare('INSERT OR IGNORE INTO cuotas (user_id, periodo, monto) VALUES (?, ?, ?)')
            ->execute([$userId, date('Y-m'), $monto]);
    }
}

/** true si el socio no debe ninguna cuota. */
function cuota_al_dia(PDO $pdo, string $userId): bool {
    generar_cuota_mes($pdo, $userId);
    $st = $pdo->prepare("SELECT COUNT(*) FROM cuotas WHERE user_id = ? AND estado = 'pendiente'");
    $st->execute([$userId]);
    return $st->fetchColumn() == 0;
}

/** Precio de un sector: los socios Pleno CON LA CUOTA AL DÍA tienen la Tribuna Popular gratis. */
function precio_sector(PDO $pdo, string $userId, string $sector): float {
    $st = $pdo->prepare('SELECT categoria FROM users WHERE id = ?');
    $st->execute([$userId]);
    $esPleno = str_starts_with((string)$st->fetchColumn(), 'Pleno');
    $gratis = $esPleno && SECTORES[$sector][2] && cuota_al_dia($pdo, $userId);
    return $gratis ? 0 : SECTORES[$sector][1];
}

/** URL pública del sitio (sirve en localhost, XAMPP y GitHub Codespaces). */
function url_base(): string {
    if ($url = env('APP_URL')) return rtrim($url, '/');
    $host  = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
    $https = ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    return ($https ? 'https' : 'http') . '://' . $host;
}

/** Llama a la API de Mercado Pago. Devuelve el JSON como array o tira una excepción con el motivo. */
function mp_api(string $metodo, string $ruta, ?array $datos = null): array {
    $token = env('MP_ACCESS_TOKEN');
    if (!$token) throw new RuntimeException('Falta MP_ACCESS_TOKEN en el archivo .env');
    if (!function_exists('curl_init')) throw new RuntimeException('La extensión cURL de PHP no está activada');

    $ch = curl_init('https://api.mercadopago.com' . $ruta);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $metodo,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'X-Idempotency-Key: ' . bin2hex(random_bytes(16)),
        ],
    ]);
    if ($datos !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));

    $respuesta = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errorCurl = curl_error($ch);
    curl_close($ch);

    if ($respuesta === false) throw new RuntimeException('No se pudo conectar con Mercado Pago: ' . $errorCurl);
    $json = json_decode($respuesta, true) ?: [];
    if ($codigo >= 400) {
        $detalle = $json['message'] ?? $respuesta;
        error_log("Mercado Pago respondió $codigo: $respuesta");
        throw new RuntimeException("Mercado Pago respondió $codigo: $detalle");
    }
    return $json;
}

/**
 * Inicia la compra de una entrada.
 * Devuelve ['gratis' => true] si no hay que pagar (la entrada ya queda guardada)
 * o ['init_point' => url] para mandar al usuario a pagar a Mercado Pago.
 * Si algo no es válido tira una excepción con un mensaje para mostrar al usuario.
 */
function crear_compra(PDO $pdo, string $userId, string $matchId, string $sector): array {
    if (!isset(SECTORES[$sector])) throw new InvalidArgumentException('Elegí un sector válido.');

    $st = $pdo->prepare('SELECT m.*, (SELECT COUNT(*) FROM tickets t WHERE t.match_id = m.id) AS vendidas
                         FROM matches m WHERE m.id = ? AND m.match_date >= ?');
    $st->execute([$matchId, date('Y-m-d H:i:s')]);
    $partido = $st->fetch();
    if (!$partido) throw new InvalidArgumentException('Ese partido no existe o ya se jugó.');
    if (isset($partido['capacity']) && $partido['vendidas'] >= $partido['capacity']) throw new InvalidArgumentException('No quedan entradas para ese partido.');

    $nombreSector = SECTORES[$sector][0];
    $ya = $pdo->prepare('SELECT 1 FROM tickets WHERE user_id = ? AND match_id = ? AND sector = ?');
    $ya->execute([$userId, $matchId, $nombreSector]);
    if ($ya->fetchColumn()) throw new InvalidArgumentException('Ya tenés una entrada para ese partido y sector.');

    $monto = precio_sector($pdo, $userId, $sector);
    if ($monto == 0) {
        $pdo->prepare('INSERT INTO tickets (user_id, match_id, sector, price) VALUES (?, ?, ?, 0)')
            ->execute([$userId, $matchId, $nombreSector]);
        return ['gratis' => true];
    }

    // Guardamos el pago pendiente: su id viaja a Mercado Pago como external_reference
    $pagoId = bin2hex(random_bytes(8));
    $pdo->prepare('INSERT INTO pagos (id, user_id, match_id, sector, monto) VALUES (?, ?, ?, ?, ?)')
        ->execute([$pagoId, $userId, $matchId, $sector, $monto]);

    $base = url_base();
    $retorno = $base . '/src/controllers/pagos/retorno.php';
    $preferencia = [
        'items' => [[
            'id'          => $matchId . '-' . $sector,
            'title'       => "Entrada PERITAS FC vs {$partido['away_team']} - $nombreSector",
            'quantity'    => 1,
            'unit_price'  => (float)$monto,
            'currency_id' => 'ARS',
        ]],
        'external_reference' => $pagoId,
        'back_urls' => [
            'success' => $retorno,
            'failure' => $retorno,
            'pending' => $retorno,
        ],
        'statement_descriptor' => 'PERITAS FC',
    ];
    // Mercado Pago solo acepta auto_return y notificaciones con URLs públicas (https, no localhost)
    if (str_starts_with($base, 'https://')) {
        $preferencia['auto_return'] = 'approved';
        $preferencia['notification_url'] = $base . '/src/controllers/pagos/webhook.php';
    }

    try {
        $resp = mp_api('POST', '/checkout/preferences', $preferencia);
    } catch (Throwable $e) {
        $pdo->prepare('DELETE FROM pagos WHERE id = ?')->execute([$pagoId]);
        throw $e;
    }
    $pdo->prepare('UPDATE pagos SET preference_id = ? WHERE id = ?')->execute([$resp['id'] ?? null, $pagoId]);

    return ['init_point' => $resp['init_point']];
}

/**
 * Inicia el pago de una cuota pendiente. Devuelve la URL de Mercado Pago (init_point).
 */
function crear_pago_cuota(PDO $pdo, string $userId, string $cuotaId): string {
    $st = $pdo->prepare("SELECT * FROM cuotas WHERE id = ? AND user_id = ? AND estado = 'pendiente'");
    $st->execute([$cuotaId, $userId]);
    $cuota = $st->fetch();
    if (!$cuota) throw new InvalidArgumentException('Esa cuota no existe o ya está pagada.');

    $pagoId = 'cuota-' . bin2hex(random_bytes(8));
    $pdo->prepare('INSERT INTO pagos_cuotas (id, cuota_id, user_id, monto) VALUES (?, ?, ?, ?)')
        ->execute([$pagoId, $cuota['id'], $userId, $cuota['monto']]);

    $base = url_base();
    $retorno = $base . '/src/controllers/pagos/retorno.php';
    $preferencia = [
        'items' => [[
            'id'          => 'cuota-' . $cuota['periodo'],
            'title'       => 'Cuota social PERITAS FC - ' . $cuota['periodo'],
            'quantity'    => 1,
            'unit_price'  => (float)$cuota['monto'],
            'currency_id' => 'ARS',
        ]],
        'external_reference' => $pagoId,
        'back_urls' => ['success' => $retorno, 'failure' => $retorno, 'pending' => $retorno],
        'statement_descriptor' => 'PERITAS FC',
    ];
    if (str_starts_with($base, 'https://')) {
        $preferencia['auto_return'] = 'approved';
        $preferencia['notification_url'] = $base . '/src/controllers/pagos/webhook.php';
    }

    try {
        $resp = mp_api('POST', '/checkout/preferences', $preferencia);
    } catch (Throwable $e) {
        $pdo->prepare('DELETE FROM pagos_cuotas WHERE id = ?')->execute([$pagoId]);
        throw $e;
    }
    return $resp['init_point'];
}

/** Marca como pagada la cuota de un pago aprobado en Mercado Pago. */
function aplicar_pago_cuota(PDO $pdo, array $pagoMp, string $mpPaymentId): string {
    $st = $pdo->prepare('SELECT * FROM pagos_cuotas WHERE id = ?');
    $st->execute([$pagoMp['external_reference'] ?? '']);
    $pago = $st->fetch();
    if (!$pago) throw new RuntimeException('El pago no corresponde a ninguna cuota registrada.');
    if ($pago['estado'] === 'aprobado') return 'aprobado';

    $estadoMp = $pagoMp['status'] ?? '';
    if ($estadoMp === 'approved' && (float)$pagoMp['transaction_amount'] >= (float)$pago['monto']) {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE pagos_cuotas SET estado = 'aprobado', mp_payment_id = ? WHERE id = ?")
            ->execute([$mpPaymentId, $pago['id']]);
        $pdo->prepare("UPDATE cuotas SET estado = 'pagada', fecha_pago = ? WHERE id = ?")
            ->execute([date('Y-m-d H:i:s'), $pago['cuota_id']]);
        $pdo->commit();
        return 'aprobado';
    }
    if (in_array($estadoMp, ['rejected', 'cancelled', 'refunded', 'charged_back'], true)) {
        $pdo->prepare("UPDATE pagos_cuotas SET estado = 'rechazado', mp_payment_id = ? WHERE id = ?")
            ->execute([$mpPaymentId, $pago['id']]);
        return 'rechazado';
    }
    return 'pendiente';
}


/**
 * Consulta un pago en Mercado Pago y actualiza la base.
 * Si está aprobado crea la entrada (una sola vez). Devuelve el estado: aprobado, pendiente o rechazado.
 */
function confirmar_pago(PDO $pdo, string $mpPaymentId): string {
    $pagoMp = mp_api('GET', '/v1/payments/' . rawurlencode($mpPaymentId));
        if (str_starts_with($pagoMp['external_reference'] ?? '', 'cuota-')) {
        return aplicar_pago_cuota($pdo, $pagoMp, $mpPaymentId);
    }

    $st = $pdo->prepare('SELECT * FROM pagos WHERE id = ?');
    $st->execute([$pagoMp['external_reference'] ?? '']);
    $pago = $st->fetch();
    if (!$pago) throw new RuntimeException('El pago no corresponde a ninguna compra registrada.');
    if ($pago['estado'] === 'aprobado') return 'aprobado';

    $estadoMp = $pagoMp['status'] ?? '';
    if ($estadoMp === 'approved' && (float)$pagoMp['transaction_amount'] >= (float)$pago['monto']) {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE pagos SET estado = 'aprobado', mp_payment_id = ? WHERE id = ?")
            ->execute([$mpPaymentId, $pago['id']]);
        $pdo->prepare('INSERT OR IGNORE INTO tickets (user_id, match_id, sector, price) VALUES (?, ?, ?, ?)')
            ->execute([$pago['user_id'], $pago['match_id'], SECTORES[$pago['sector']][0], $pago['monto']]);
        $pdo->commit();
        return 'aprobado';
    }
    if (in_array($estadoMp, ['rejected', 'cancelled', 'refunded', 'charged_back'], true)) {
        $pdo->prepare("UPDATE pagos SET estado = 'rechazado', mp_payment_id = ? WHERE id = ?")
            ->execute([$mpPaymentId, $pago['id']]);
        return 'rechazado';
    }
    return 'pendiente';
}