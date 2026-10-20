<?php
// src/controllers/pagos/webhook.php
require_once __DIR__ . '/../../config/mercadopago.php';

$cuerpo = json_decode(file_get_contents('php://input'), true) ?? [];

// Mercado Pago puede enviar el id por query string o por el body
$tipo      = $_GET['type'] ?? $_GET['topic'] ?? ($cuerpo['type'] ?? '');
$paymentId = $_GET['data_id'] ?? $_GET['id'] ?? ($cuerpo['data']['id'] ?? null);

if ($tipo === 'payment' && $paymentId) {
    try {
        // Consulta oficial: nunca confiar solo en el webhook
        $pago = mpRequest('GET', '/v1/payments/' . urlencode((string)$paymentId));

        $db = dbPagos();
        $db->prepare('UPDATE pagos_mp
                      SET estado = ?, mp_payment_id = ?, actualizado = CURRENT_TIMESTAMP
                      WHERE external_reference = ?')
           ->execute([$pago['status'], (string)$pago['id'], $pago['external_reference']]);

        if ($pago['status'] === 'approved') {
            // Acá activás la cuota del socio o registrás la entrada en tus tablas.
            // Ej: UPDATE usuarios SET estado_cuota='AL_DIA' WHERE id = (SELECT usuario_id ...)
        }
    } catch (Throwable $e) {
        error_log('Webhook MP: ' . $e->getMessage());
    }
}

http_response_code(200);
echo 'OK';
