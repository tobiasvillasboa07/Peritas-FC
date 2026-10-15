<?php
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        header('Location: /src/views/auth/login.php?error=Campos obligatorios');
        exit;
    }

    try {
        // Consulta usando nuestra conexión PDO de SQLite
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificación de contraseña
        if ($user && ($password === $user['password'] || password_verify($password, $user['password']))) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];

            // Redirección absoluta limpia hacia el index de vistas
            header('Location: /src/views/index.php');
            exit;
        } else {
            header('Location: /src/views/auth/login.php?error=Credenciales incorrectas');
            exit;
        }
    } catch (PDOException $e) {
        header('Location: /src/views/auth/login.php?error=Error de conexión');
        exit;
    }
} else {
    header('Location: /src/views/auth/login.php');
    exit;
}