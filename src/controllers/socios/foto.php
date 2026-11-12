<?php
// Sube, cambia o saca la foto de la credencial del socio (desde el portal)
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../models/foto_socio.php';

if (empty($_SESSION['user_id'])) { header('Location: /src/views/auth/login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['borrar'])) {
            borrar_foto_socio($pdo, $_SESSION['user_id']);
        } else {
            guardar_foto_socio($pdo, $_SESSION['user_id'], $_FILES['foto'] ?? []);
        }
    } catch (InvalidArgumentException $e) {
        $_SESSION['foto_error'] = $e->getMessage();
    }
}
// Volvemos al portal con GET (Post/Redirect/Get), así "Atrás" no pide reenviar la foto
header('Location: /src/views/portal.php#credencial');
exit;
