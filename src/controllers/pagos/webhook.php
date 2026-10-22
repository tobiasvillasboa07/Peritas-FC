<?php
/**
 * Notificaciones de Mercado Pago (notification_url). Solo funciona con una URL pública https,
 * por ejemplo el puerto de Codespaces en modo "Public". Si no llega, retorno.php igual confirma el pago.
 */
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/mercadopago.php';

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$tipo = $body['type'] ?? $_GET['type'] ?? $_GET['topic'] ?? '';
$id   = $body['data']['id'] ?? $_GET['data_id'] ?? $_GET['id'] ?? '';

if ($tipo === 'payment' && $id !== '') {
    try {
        confirmar_pago($pdo, (string)$id);
    } catch (Throwable $e) {
        error_log('Webhook Mercado Pago: ' . $e->getMessage());
    }
}

http_response_code(200);
echo 'OK';