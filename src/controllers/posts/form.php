<?php
// Formulario para crear o editar una noticia (solo admin)
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/admin.php';
require_once __DIR__ . '/../../models/noticias.php';
requerir_admin($pdo);

$post = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ?');
    $stmt->execute([$_GET['id']]);
    $post = $stmt->fetch() ?: null;
}
$imagenes = imagenes_disponibles();

require_once __DIR__ . '/../../views/posts/form.php';