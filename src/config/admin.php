<?php
/**
 * Corta la página si quien entra no es administrador.
 * Devuelve los datos del admin (id, name, role) para usarlos en la página.
 */
function requerir_admin(PDO $pdo): array {
    if (empty($_SESSION['user_id'])) {
        header('Location: /src/views/auth/login.php');
        exit;
    }
    $st = $pdo->prepare('SELECT id, name, role FROM users WHERE id = ?');
    $st->execute([$_SESSION['user_id']]);
    $user = $st->fetch();
    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        exit('Esta sección es solo para administradores.');
    }
    return $user;
}