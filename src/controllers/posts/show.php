<?php
require_once __DIR__ . '/../../config/bootstrap.php';

// Capturar el ID de la URL de forma segura
$id = $_GET['id'] ?? null;

if (!$id) {
    exit('ID de post no proporcionado.');
}

try {
    $query = '
        SELECT 
            p.*,
            u.id AS user_id,
            u.name AS user_name
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = :id
    ';

    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $id]);

    // Obtenemos un único registro
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        exit('Publicación no encontrada.');
    }

    // Cargamos la vista de detalle
    require_once __DIR__ . '/../../views/posts/show.php';
} catch (PDOException $e) {
    exit('Error al consultar el post');
}