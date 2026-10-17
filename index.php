<?php
require_once __DIR__ . '/src/config/bootstrap.php';

$u = null;
if (!empty($_SESSION['user_id'])) {
    $st = $pdo->prepare('SELECT name, categoria, role FROM users WHERE id = ?');
    $st->execute([$_SESSION['user_id']]);
    $u = $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

$html = file_get_contents(__DIR__ . '/inicio.html');
$inject = '<script>window.PFC_USER = ' . json_encode($u, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) . ';</script>';
echo str_replace('</head>', $inject . '</head>', $html);