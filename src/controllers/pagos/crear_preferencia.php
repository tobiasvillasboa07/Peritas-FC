<?php
// src/controllers/pagos/crear_preferencia.php
require_once __DIR__ . '/../../config/mercadopago.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Ajustá estas claves según lo que guarda tu login.php
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Debés iniciar sesión para pagar.']);
    exit;
}

$usuarioId = $_SESSION['user_id'];
$nombre    = $_SESSION['user_name']  ?? 'Socio';
$email     = $_SESSION['user_email'] ?? 'socio@peritas.com';
$esSocio   = ($_SESSION['estado_cuota'] ?? '') === 'AL_DIA';
$categoria = $_SESSION['user_category'] ?? 'PLENO';

$entrada = json_decode(file_get_contents('php://input'), true) ?? [];
$tipo    = $entrada['tipo'] ?? '';

// ---- PRECIOS: siempre definidos en el servidor, nunca desde el cliente ----
$cuotas = ['PLENO' => 5000, 'ADHERENTE' => 3000];

$partidos = [
    101 => ['titulo' => 'Entrada vs Alianza Deportiva', 'sectores' => [
        'popular' => 8000, 'popular_sur' => 8000, 'platea' => 15000, 'platea_baja' => 22500]],
    102 => ['titulo' => 'Entrada vs Unión Tecnológica', 'sectores' => [
        'popular' => 9000, 'popular_sur' => 9000, 'platea' => 18000, 'platea_baja' => 27000]],
];

try {
    if ($tipo === 'cuota') {
        $concepto = 'Cuota Social ' . $categoria . ' - ' . date('m/Y');
        $monto    = $cuotas[$categoria] ?? 5000;

    } elseif ($tipo === 'entrada') {
        $partido = $partidos[(int)($entrada['partidoId'] ?? 0)] ?? null;
        $sector  = $entrada['sector'] ?? '';
        if (!$partido || !isset($partido['sectores'][$sector])) {
            throw new InvalidArgumentException('Partido o sector inválido.');
        }
        $concepto = $partido['titulo'] . ' (' . strtoupper($sector) . ')';
        $monto    = $partido['sectores'][$sector];

        // Beneficios de socio al día (igual que en el frontend)
        if ($esSocio) {
            if (str_starts_with($sector, 'popular')) {
                echo json_encode(['gratis' => true]);   // no requiere pago
                exit;
            }
            $monto = $monto * 0.5;
        }
    } else {
        throw new InvalidArgumentException('Tipo de pago inválido.');
    }

    $referencia = 'PFC-' . strtoupper($tipo) . '-' . bin2hex(random_bytes(6));

    // Guardar el intento como pendiente antes de ir a Mercado Pago
    $db = dbPagos();
    $db->prepare('INSERT INTO pagos_mp (external_reference, usuario_id, concepto, monto, estado)
                  VALUES (?, ?, ?, ?, \'pendiente\')')
       ->execute([$referencia, $usuarioId, $concepto, $monto]);

    // Crear la preferencia (Checkout Pro)
    $preferencia = mpRequest('POST', '/checkout/preferences', [
        'items' => [[
            'title'       => $concepto,
            'quantity'    => 1,
            'unit_price'  => (float)$monto,
            'currency_id' => 'ARS',
        ]],
        'payer' => ['name' => $nombre, 'email' => $email],
        'external_reference' => $referencia,
        'statement_descriptor' => 'PERITAS FC',
        'back_urls' => [
            'success' => APP_URL . '/src/views/pagos/resultado.php?estado=aprobado',
            'failure' => APP_URL . '/src/views/pagos/resultado.php?estado=rechazado',
            'pending' => APP_URL . '/src/views/pagos/resultado.php?estado=pendiente',
        ],
        'auto_return'       => 'approved',
        'notification_url'  => APP_URL . '/src/controllers/pagos/webhook.php',
    ]);

    echo json_encode([
        'init_point' => $preferencia['init_point'],   // producción
        'sandbox'    => $preferencia['sandbox_init_point'] ?? null,
    ]);

} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo iniciar el pago. Intentá de nuevo.']);
}
