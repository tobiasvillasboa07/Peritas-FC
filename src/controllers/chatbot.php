<?php
/**
 * Asistente virtual del club.
 * POST mensaje=...  ->  { "respuesta": "..." }
 * Responde con datos reales de la base (próximo partido, precios, cuotas)
 * y con las preguntas frecuentes de la tabla bot_faqs. Guarda la charla en bot_messages.
 */
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/mercadopago.php';   // SECTORES y cuotas

header('Content-Type: application/json; charset=utf-8');

$mensaje = trim($_POST['mensaje'] ?? '');
if ($mensaje === '') {
    echo json_encode(['respuesta' => 'Escribime tu consulta y te ayudo.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$uid = $_SESSION['user_id'] ?? null;
$nombre = 'Invitado';
if ($uid) {
    $st = $pdo->prepare('SELECT name FROM users WHERE id = ?');
    $st->execute([$uid]);
    $nombre = $st->fetchColumn() ?: 'Invitado';
}
$nombre = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');

/** Minúsculas y sin acentos, para comparar palabras fácil. */
function normalizar(string $t): string {
    return strtr(mb_strtolower($t), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
}
function contiene(string $texto, array $palabras): bool {
    foreach ($palabras as $p) if (str_contains($texto, $p)) return true;
    return false;
}
function plata($n): string { return 'AR$ ' . number_format((float)$n, 0, ',', '.'); }

function responder(PDO $pdo, string $m, string $nombre, ?string $uid): string {
    if (contiene($m, ['partido', 'juega', 'juegan', 'proximo', 'fecha', 'rival'])) {
        $st = $pdo->prepare('SELECT * FROM matches WHERE match_date >= ? ORDER BY match_date LIMIT 1');
        $st->execute([date('Y-m-d H:i:s')]);
        $p = $st->fetch();
        if (!$p) return 'Todavía no hay partidos cargados. ¡Atento a las novedades!';
        return sprintf('El próximo partido es <b>%s vs %s</b> por %s, el %s a las %s hs en el %s. Sacá tu entrada en la sección ENTRADAS. ⚽',
            htmlspecialchars($p['home_team']), htmlspecialchars($p['away_team']), htmlspecialchars($p['tournament']),
            date('d/m', strtotime($p['match_date'])), date('H:i', strtotime($p['match_date'])), htmlspecialchars($p['stadium']));
    }
    if (contiene($m, ['entrada', 'ticket', 'precio', 'cuanto sale', 'platea', 'popular'])) {
        $lista = implode(', ', array_map(fn($s) => $s[0] . ' ' . plata($s[1]), SECTORES));
        return "Precios de las entradas: $lista. Los socios Pleno con la cuota al día entran gratis a la Tribuna Popular.";
    }
    if (contiene($m, ['cuota', 'debo', 'deuda', 'pagar', 'pago'])) {
        if (!$uid) return 'Iniciá sesión y te digo cómo está tu cuota. La pagás desde el PORTAL SOCIO con Mercado Pago.';
        generar_cuota_mes($pdo, $uid);
        $st = $pdo->prepare("SELECT COUNT(*), COALESCE(SUM(monto), 0) FROM cuotas WHERE user_id = ? AND estado = 'pendiente'");
        $st->execute([$uid]);
        [$cant, $total] = $st->fetch(PDO::FETCH_NUM);
        return $cant > 0
            ? "$nombre, tenés $cant cuota(s) sin pagar por " . plata($total) . '. Pagala desde el PORTAL SOCIO con Mercado Pago.'
            : "¡Estás al día, $nombre! No debés ninguna cuota. 💙💛";
    }
    if (contiene($m, ['asociar', 'socio', 'categoria', 'hacerme'])) {
        $cats = $pdo->query('SELECT nombre, cuota_mensual FROM categorias ORDER BY cuota_mensual DESC')->fetchAll();
        $lista = implode(' · ', array_map(fn($c) => htmlspecialchars($c['nombre']) . ': ' . plata($c['cuota_mensual']) . '/mes', $cats));
        return "Podés asociarte con el botón ASOCIARSE de arriba. Categorías: $lista.";
    }
    if (contiene($m, ['hola', 'buenas', 'buen dia', 'que tal'])) {
        return "¡Hola $nombre! ⚽ Soy el asistente de Peritas FC. Preguntame por el próximo partido, las entradas, tu cuota o la sede.";
    }
    if (contiene($m, ['gracias', 'chau', 'adios'])) {
        return "¡De nada, $nombre! Vamos Peritas FC 💙💛";
    }
    // Preguntas frecuentes cargadas en la base (bot_faqs)
    foreach ($pdo->query('SELECT keyword, response_text FROM bot_faqs')->fetchAll() as $faq) {
        $clave = str_replace('_', ' ', normalizar($faq['keyword']));
        if (str_contains($m, $clave) || ($clave === 'sede' && contiene($m, ['donde', 'direccion', 'ubicacion', 'queda']))) {
            return htmlspecialchars($faq['response_text']);
        }
    }
    return 'No entendí bien tu consulta 🤔. Podés preguntarme por el próximo partido, las entradas, tu cuota, cómo asociarte o dónde queda la sede.';
}

$respuesta = responder($pdo, normalizar($mensaje), $nombre, $uid);

// Guardamos la charla (si falla, igual respondemos)
try {
    $ins = $pdo->prepare('INSERT INTO bot_messages (user_id, sender, message) VALUES (?, ?, ?)');
    $ins->execute([$uid, 'user', $mensaje]);
    $ins->execute([$uid, 'bot', strip_tags($respuesta)]);
} catch (PDOException $e) {
    error_log('No se pudo guardar el mensaje del bot: ' . $e->getMessage());
}

echo json_encode(['respuesta' => $respuesta], JSON_UNESCAPED_UNICODE);