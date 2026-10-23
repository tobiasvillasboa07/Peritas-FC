<?php
header('Location: /src/views/index.php'); exit;
require_once __DIR__ . '/src/config/bootstrap.php';
require_once __DIR__ . '/src/config/mercadopago.php';
require_once __DIR__ . '/src/config/noticias.php';

$u = null;
if (!empty($_SESSION['user_id'])) {
    $st = $pdo->prepare('SELECT name, email, categoria, role FROM users WHERE id = ?');
    $st->execute([$_SESSION['user_id']]);
    $u = $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Próximos partidos de la base, con el precio de cada sector para este usuario
$q = $pdo->prepare('SELECT id, away_team, tournament, stadium, match_date FROM matches WHERE match_date >= ? ORDER BY match_date');
$q->execute([date('Y-m-d H:i:s')]);
$partidos = [];
foreach ($q->fetchAll() as $m) {
    $sectores = [];
    foreach (SECTORES as $k => [$nombre, $precio]) {
        $sectores[] = [
            'key'    => $k,
            'nombre' => $nombre,
            'precio' => $precio,
            'final'  => $u ? precio_sector($pdo, $_SESSION['user_id'], $k) : $precio,
        ];
    }
    $partidos[] = [
        'id'         => $m['id'],
        'opponent'   => $m['away_team'],
        'tournament' => $m['tournament'],
        'stadium'    => $m['stadium'],
        'date'       => date('d/m/Y H:i', strtotime($m['match_date'])) . ' hs',
        'sectores'   => $sectores,
    ];
}

// Noticias cargadas por el admin (la primera vez se copian de noticias.json)
sembrar_noticias($pdo);
$noticias = noticias_para_pagina($pdo);

$html = file_get_contents(__DIR__ . '/inicio.html');
$inject = '<script>window.PFC_USER = ' . json_encode($u, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) . ';'
        . 'window.PFC_MATCHES = ' . json_encode($partidos, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) . ';'
        . 'window.PFC_NEWS = ' . json_encode($noticias, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) . ';</script>';
echo str_replace('</head>', $inject . '</head>', $html);