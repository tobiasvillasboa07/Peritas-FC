<?php
/**
 * Mercado Pago manda al usuario acá después de pagar (back_urls).
 * Llega con ?payment_id=...&status=...&external_reference=...
 * Verificamos el pago contra la API (no confiamos en el "status" de la URL) y volvemos a la página.
 */
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/mercadopago.php';

$paymentId = $_GET['payment_id'] ?? $_GET['collection_id'] ?? '';
$estado = 'rechazado';

if ($paymentId !== '' && $paymentId !== 'null') {
    try {
        $estado = confirmar_pago($pdo, $paymentId);
    } catch (Throwable $e) {
        error_log('Error confirmando el pago: ' . $e->getMessage());
        $estado = 'error';
    }
}

// Las cuotas vuelven al portal del socio; las entradas, a la sección de entradas
if (str_starts_with($_GET['external_reference'] ?? '', 'cuota-')) {
    header('Location: /src/views/index.php?pago=' . urlencode($estado) . '#cuotas');
} else {
    header('Location: /index.php?pago=' . urlencode($estado) . '#tickets');
}
exit;