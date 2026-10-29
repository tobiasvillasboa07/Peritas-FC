<?php
// Admin de noticias: listado con editar y borrar
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/admin.php';
require_once __DIR__ . '/../../models/noticias.php';
requerir_admin($pdo);
sembrar_noticias($pdo);

$posts = $pdo->query('SELECT p.*, u.name AS user_name FROM posts p LEFT JOIN users u ON u.id = p.user_id ORDER BY p.created_at DESC')->fetchAll();

require_once __DIR__ . '/../../views/posts/index.php';