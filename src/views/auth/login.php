<?php
require_once __DIR__ . '/../../config/bootstrap.php';

// Si ya inició sesión, lo mandamos al portal
if (!empty($_SESSION['user_id'])) {
    header('Location: /src/views/index.php');
    exit;
}

// Mensaje y email del intento anterior (si falló); se borran para que no se repitan
$error = $_SESSION['login_error'] ?? '';
$emailAnterior = $_SESSION['login_email'] ?? '';
unset($_SESSION['login_error'], $_SESSION['login_email']);
$aviso = isset($_GET['registrado']) ? '¡Cuenta creada! Ya podés iniciar sesión.'
       : (isset($_GET['restablecida']) ? 'Listo, cambiaste tu contraseña. Ya podés iniciar sesión.' : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT id, name, email, password, role, categoria FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['user'] = [
            'id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'],
            'role' => $user['role'], 'categoria' => $user['categoria'],
        ];
        header('Location: /src/views/index.php');
        exit;
    }
    // Si falla, volvemos al login con GET: así el botón "Atrás" no pide reenviar el formulario
    $_SESSION['login_error'] = 'Correo o contraseña incorrectos.';
    $_SESSION['login_email'] = $email;
    header('Location: /src/views/auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Peritas FC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/dala.css">
    <link rel="icon" type="image/png" href="/assets/img/escudo-chico.png">
</head>
<body class="flex flex-col min-h-screen bg-[#000000] text-slate-100 font-['Poppins'] justify-center items-center p-4">

    <div class="max-w-md w-full bg-[#0a0a0a] border border-[#2a2a2a]/50 rounded-2xl p-8 space-y-6 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-[#ffb829] to-yellow-500"></div>

        <div class="text-center space-y-2">
            <img src="/assets/img/escudo.png" alt="Escudo de Peritas FC" class="h-24 w-auto mx-auto mb-2">
            <h1 class="text-2xl font-extrabold text-white">Ingresá a tu Cuenta</h1>
            <p class="text-xs text-slate-400">Iniciá sesión para gestionar tu carnet digital en Peritas FC</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/40 text-red-300 text-xs rounded-xl px-4 py-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($aviso): ?>
            <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-300 text-xs rounded-xl px-4 py-3"><?= htmlspecialchars($aviso) ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-300 mb-1">Correo Electrónico</label>
                <input type="email" name="email" required placeholder="socio@peritas.com" value="<?= htmlspecialchars($emailAnterior) ?>" class="w-full bg-slate-900 border border-[#2a2a2a] px-4 py-2.5 rounded-xl text-slate-100 focus:outline-none focus:border-[#ffb829] transition text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-300 mb-1">Contraseña</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full bg-slate-900 border border-[#2a2a2a] px-4 py-2.5 rounded-xl text-slate-100 focus:outline-none focus:border-[#ffb829] transition text-sm">
                <a href="olvide.php" class="inline-block mt-2 text-xs text-[#ffb829] hover:underline">¿Olvidaste tu contraseña?</a>
            </div>

            <button type="submit" class="w-full py-3 bg-gradient-to-r from-[#ffb829] to-yellow-500 text-[#000000] font-black tracking-wide rounded-xl shadow-lg hover:scale-[1.01] transition">
                INGRESAR
            </button>
        </form>

        <div class="text-center border-t border-[#2a2a2a]/30 pt-4">
            <p class="text-xs text-slate-400">¿Todavía no sos socio? <a href="register.php" class="text-[#ffb829] hover:underline font-bold">Asociate acá</a></p>
        </div>
    </div>

</body>
</html>