<?php
/**
 * POST /src/controllers/pagos/crear.php   body JSON: { "match_id": "...", "sector": "popular_norte" }
 * Responde JSON: { ok: true, init_point: "https://..." }  o  { ok: true, gratis: true }  o  { ok: false, error: "..." }
 */
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/mercadopago.php';

header('Content-Type: application/json; charset=utf-8');

function responder(int $codigo, array $datos) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(405, ['ok' => false, 'error' => 'Método no permitido.']);
if (empty($_SESSION['user_id'])) responder(401, ['ok' => false, 'error' => 'Tenés que iniciar sesión para comprar entradas.']);

$body = json_decode(file_get_contents('php://input'), true) ?: $_POST;

try {
    $r = crear_compra($pdo, $_SESSION['user_id'], (string)($body['match_id'] ?? ''), (string)($body['sector'] ?? ''));
    responder(200, ['ok' => true] + $r);
} catch (InvalidArgumentException $e) {
    responder(422, ['ok' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('Error creando el pago: ' . $e->getMessage());
    responder(500, ['ok' => false, 'error' => 'No se pudo iniciar el pago.', 'detalle' => $e->getMessage()]);
}