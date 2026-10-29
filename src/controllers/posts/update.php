<?php
// Guarda los cambios de una noticia (solo admin)
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/admin.php';
require_once __DIR__ . '/../../models/noticias.php';
requerir_admin($pdo);

$id       = $_POST['id'] ?? '';
$title    = trim($_POST['title'] ?? '');
$content  = trim($_POST['content'] ?? '');
$category = isset(CATEGORIAS_NOTICIAS[$_POST['category'] ?? '']) ? $_POST['category'] : 'futbol';
$image    = trim($_POST['image_url'] ?? '') ?: null;

if ($title === '' || $content === '') {
    header('Location: /src/controllers/posts/form.php?id=' . urlencode($id) . '&error=1');
    exit;
}

$pdo->prepare('UPDATE posts SET title = ?, content = ?, category = ?, image_url = ? WHERE id = ?')
    ->execute([$title, $content, $category, $image, $id]);

header('Location: /src/controllers/posts/index.php?ok=editada');
exit;