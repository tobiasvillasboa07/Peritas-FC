<?php
/**
 * Recuperar la contraseña con un link por mail.
 *
 * Flujo:
 *  1. El socio pone su mail en views/auth/olvide.php.
 *  2. pedir_recuperacion() genera un código al azar (token), guarda SOLO su hash
 *     y le manda al socio un mail con el link views/auth/restablecer.php?token=...
 *  3. El link sirve una sola vez y vence a los 30 minutos.
 *  4. En restablecer.php el socio elige la contraseña nueva (restablecer_contrasena()).
 *
 * Los mails se mandan con la API de Brevo usando cURL (igual que Mercado Pago).
 * Necesita en el .env:  BREVO_API_KEY=xkeysib-...  MAIL_FROM=tu@mail.com  APP_URL=https://tu-url
 * Sin BREVO_API_KEY (Codespaces o tu compu) el link se muestra en la terminal de php -S, para probar.
 */
require_once __DIR__ . '/../config/env.php';

const MINUTOS_LINK   = 30;  // cuánto dura el link
const LINKS_POR_HORA = 3;   // máximo de mails por socio por hora (para que no le llenen la casilla)

// Tabla de pedidos de recuperación (se crea sola si la base ya existía antes de este cambio)
$pdo->exec("CREATE TABLE IF NOT EXISTS recuperaciones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,
    token_hash TEXT NOT NULL UNIQUE,
    expira DATETIME NOT NULL,
    usado INTEGER NOT NULL DEFAULT 0 CHECK (usado IN (0, 1)),
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

/**
 * Si el mail es de un socio, le manda el link para cambiar la contraseña.
 * No devuelve nada: la página muestra el mismo mensaje exista o no el mail,
 * así nadie puede averiguar quién es socio.
 */
function pedir_recuperacion(PDO $pdo, string $email): void {
    $st = $pdo->prepare('SELECT id, name, email FROM users WHERE lower(email) = ?');
    $st->execute([$email]);
    $user = $st->fetch();
    if (!$user) return;

    $st = $pdo->prepare('SELECT COUNT(*) FROM recuperaciones WHERE user_id = ? AND created_at > ?');
    $st->execute([$user['id'], date('Y-m-d H:i:s', strtotime('-1 hour'))]);
    if ($st->fetchColumn() >= LINKS_POR_HORA) return;

    // 32 bytes al azar = 64 caracteres: imposible de adivinar (el DNI tiene solo 8 números)
    $token = bin2hex(random_bytes(32));
    // Como con las contraseñas, en la base queda solo el hash: si alguien la roba, no le sirven los links
    $pdo->prepare('INSERT INTO recuperaciones (user_id, token_hash, expira, created_at) VALUES (?, ?, ?, ?)')
        ->execute([$user['id'], hash('sha256', $token), date('Y-m-d H:i:s', strtotime('+' . MINUTOS_LINK . ' minutes')), date('Y-m-d H:i:s')]);

    try {
        mandar_mail_recuperacion($user, $token);
    } catch (RuntimeException $e) {
        error_log('No se pudo mandar el mail de recuperación: ' . $e->getMessage());
    }
}

/** Devuelve el pedido si el link es válido (existe, no se usó y no venció), o null. */
function buscar_recuperacion(PDO $pdo, string $token): ?array {
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) return null;
    $st = $pdo->prepare('SELECT * FROM recuperaciones WHERE token_hash = ? AND usado = 0 AND expira > ?');
    $st->execute([hash('sha256', $token), date('Y-m-d H:i:s')]);
    return $st->fetch() ?: null;
}

/** Cambia la contraseña usando el link. Devuelve false si el link ya no sirve. */
function restablecer_contrasena(PDO $pdo, string $token, string $password): bool {
    $pedido = buscar_recuperacion($pdo, $token);
    if (!$pedido) return false;

    $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
        ->execute([password_hash($password, PASSWORD_DEFAULT), $pedido['user_id']]);
    // Se dan por usados TODOS los links del socio: el que usó y cualquier otro que haya pedido
    $pdo->prepare('UPDATE recuperaciones SET usado = 1 WHERE user_id = ?')->execute([$pedido['user_id']]);
    return true;
}

/** Manda el mail con el link usando la API de Brevo. Si algo falla tira una excepción con el motivo. */
function mandar_mail_recuperacion(array $user, string $token): void {
    $ruta = '/src/views/auth/restablecer.php?token=' . $token;

    $clave = env('BREVO_API_KEY');
    if (!$clave) {
        // Modo prueba: sin Brevo el link aparece en la terminal donde corre php -S
        error_log('MODO PRUEBA - link para cambiar la contraseña de ' . $user['email'] . ': ' . env('APP_URL', '') . $ruta);
        return;
    }
    // El link se arma con APP_URL (y no con la dirección que manda el navegador) para que siempre apunte a NUESTRA página
    $base = env('APP_URL');
    if (!$base) throw new RuntimeException('Falta APP_URL en el .env');
    $remitente = env('MAIL_FROM');
    if (!$remitente) throw new RuntimeException('Falta MAIL_FROM en el .env');

    $link   = rtrim($base, '/') . $ruta;
    $nombre = htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8');
    $html   = "<p>Hola $nombre:</p>"
            . '<p>Pediste cambiar tu contraseña de Peritas FC. Tocá el botón para elegir una nueva:</p>'
            . '<p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:12px 20px;background:#ffb829;color:#000;font-weight:bold;text-decoration:none;border-radius:10px">Cambiar contraseña</a></p>'
            . '<p>El link vence en ' . MINUTOS_LINK . ' minutos y sirve una sola vez.</p>'
            . '<p>Si no lo pediste vos, ignorá este mail: tu contraseña sigue siendo la misma.</p>';
    $texto  = "Hola {$user['name']}:\n\nPara elegir una contraseña nueva de Peritas FC entrá a este link:\n$link\n\n"
            . 'Vence en ' . MINUTOS_LINK . " minutos y sirve una sola vez. Si no lo pediste vos, ignorá este mail.";

    if (!function_exists('curl_init')) throw new RuntimeException('La extensión cURL de PHP no está activada');
    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            'api-key: ' . $clave,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_POSTFIELDS     => json_encode([
            'sender'      => ['name' => 'Peritas FC', 'email' => $remitente],
            'to'          => [['email' => $user['email'], 'name' => $user['name']]],
            'subject'     => 'Cambiá tu contraseña de Peritas FC',
            'htmlContent' => $html,
            'textContent' => $texto,
        ]),
    ]);
    $respuesta = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errorCurl = curl_error($ch);
    curl_close($ch);

    if ($respuesta === false) throw new RuntimeException('No se pudo conectar con Brevo: ' . $errorCurl);
    if ($codigo >= 400) throw new RuntimeException("Brevo respondió $codigo: $respuesta");
}
