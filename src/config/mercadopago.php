<?php
// src/config/mercadopago.php

function cargarEnv(string $ruta): void
{
    if (!is_file($ruta)) return;
    foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        if ($linea === '' || $linea[0] === '#' || !str_contains($linea, '=')) continue;
        [$clave, $valor] = array_map('trim', explode('=', $linea, 2));
        $_ENV[$clave] = $valor;
        putenv("$clave=$valor");
    }
}
cargarEnv(__DIR__ . '/../../.env');

define('MP_ACCESS_TOKEN', getenv('MP_ACCESS_TOKEN') ?: '');
define('APP_URL', rtrim(getenv('APP_URL') ?: 'http://localhost/Peritas-FC', '/'));

/** Llamada a la API REST de Mercado Pago */
function mpRequest(string $metodo, string $ruta, ?array $body = null): array
{
    $ch = curl_init('https://api.mercadopago.com' . $ruta);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $metodo,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . MP_ACCESS_TOKEN,
            'Content-Type: application/json',
            'X-Idempotency-Key: ' . uniqid('', true),
        ],
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    }
    $respuesta = curl_exec($ch);
    $codigo    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $datos = json_decode($respuesta ?: '[]', true) ?: [];
    if ($codigo >= 400) {
        throw new RuntimeException($datos['message'] ?? 'Error al comunicarse con Mercado Pago');
    }
    return $datos;
}

/** Conexión SQLite + tabla de pagos */
function dbPagos(): PDO
{
    $pdo = new PDO('sqlite:' . __DIR__ . '/../../database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS pagos_mp (
            id                INTEGER PRIMARY KEY AUTOINCREMENT,
            external_reference TEXT UNIQUE NOT NULL,
            usuario_id        INTEGER,
            concepto          TEXT,
            monto             REAL,
            estado            TEXT DEFAULT \'pendiente\',
            mp_payment_id     TEXT,
            creado            DATETIME DEFAULT CURRENT_TIMESTAMP,
            actualizado       DATETIME
        )
    ');
    return $pdo;
}
