<?php
// Borra una noticia (solo admin, y solo por POST para que no se borre con un link)
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/admin.php';
requerir_admin($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->prepare('DELETE FROM posts WHERE id = ?')->execute([$_POST['id'] ?? '']);
}

header('Location: /src/controllers/posts/index.php?ok=borrada');
exit;