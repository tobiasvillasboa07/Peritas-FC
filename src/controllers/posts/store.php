<?php
// Guarda una noticia nueva (solo admin)
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/admin.php';
require_once __DIR__ . '/../../config/noticias.php';
$admin = requerir_admin($pdo);

$title    = trim($_POST['title'] ?? '');
$content  = trim($_POST['content'] ?? '');
$category = isset(CATEGORIAS_NOTICIAS[$_POST['category'] ?? '']) ? $_POST['category'] : 'futbol';
$image    = trim($_POST['image_url'] ?? '') ?: null;

if ($title === '' || $content === '') {
    header('Location: /src/controllers/posts/form.php?error=1');
    exit;
}

$pdo->prepare('INSERT INTO posts (title, content, category, image_url, user_id) VALUES (?, ?, ?, ?, ?)')
    ->execute([$title, $content, $category, $image, $admin['id']]);

header('Location: /src/controllers/posts/index.php?ok=creada');
exit;