<?php
/**
 * Elegir la contraseña nueva con el link que llegó por mail (restablecer.php?token=...).
 * El link sirve una sola vez y vence a los 30 minutos (ver src/models/recuperar.php).
 */
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../models/recuperar.php';

$token = (string)($_POST['token'] ?? $_GET['token'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $repetir  = $_POST['repetir'] ?? '';

    if (strlen($password) < 8) {
        $_SESSION['restablecer_error'] = 'La contraseña nueva debe tener al menos 8 caracteres.';
    } elseif ($password !== $repetir) {
        $_SESSION['restablecer_error'] = 'Las contraseñas no coinciden.';
    } elseif (restablecer_contrasena($pdo, $token, $password)) {
        header('Location: login.php?restablecida=1');
        exit;
    }
    // Si algo falló volvemos con GET, igual que el login: así "Atrás" no pide reenviar el formulario
    header('Location: restablecer.php?token=' . urlencode($token));
    exit;
}

$error = $_SESSION['restablecer_error'] ?? '';
unset($_SESSION['restablecer_error']);
$valido = buscar_recuperacion($pdo, $token) !== null;
$campo = 'w-full bg-slate-900 border border-[#2a2a2a] px-4 py-2.5 rounded-xl text-slate-100 focus:outline-none focus:border-[#ffb829] transition text-sm';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- El link tiene el token: que el navegador no se lo pase a otras páginas -->
    <meta name="referrer" content="no-referrer">
    <title>Contraseña nueva - Peritas FC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/dala.css">
    <link rel="icon" type="image/png" href="/assets/img/escudo-chico.png">
</head>
<body class="flex flex-col min-h-screen bg-[#000000] text-slate-100 font-['Poppins'] justify-center items-center p-4">

    <div class="max-w-md w-full bg-[#0a0a0a] border border-[#2a2a2a]/50 rounded-2xl p-8 space-y-6 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-[#ffb829] to-yellow-500"></div>

        <div class="text-center space-y-2">
            <img src="/assets/img/escudo.png" alt="Escudo de Peritas FC" class="h-24 w-auto mx-auto mb-2">
            <h1 class="text-2xl font-extrabold text-white">Elegí tu contraseña nueva</h1>
        </div>

        <?php if (!$valido): ?>
            <div class="bg-red-500/10 border border-red-500/40 text-red-300 text-xs rounded-xl px-4 py-3">
                Este link no sirve: ya se usó o venció. Pedí uno nuevo.
            </div>
            <a href="olvide.php" class="block text-center w-full py-3 bg-gradient-to-r from-[#ffb829] to-yellow-500 text-[#000000] font-black tracking-wide rounded-xl shadow-lg hover:scale-[1.01] transition">
                PEDIR OTRO LINK
            </a>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/40 text-red-300 text-xs rounded-xl px-4 py-3"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="restablecer.php" method="POST" class="space-y-4">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-300 mb-1">Contraseña nueva</label>
                    <input type="password" name="password" required minlength="8" placeholder="••••••••" class="<?= $campo ?>">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-300 mb-1">Repetir contraseña</label>
                    <input type="password" name="repetir" required minlength="8" placeholder="••••••••" class="<?= $campo ?>">
                </div>
                <button type="submit" class="w-full py-3 bg-gradient-to-r from-[#ffb829] to-yellow-500 text-[#000000] font-black tracking-wide rounded-xl shadow-lg hover:scale-[1.01] transition">
                    CAMBIAR CONTRASEÑA
                </button>
            </form>
        <?php endif; ?>

        <div class="text-center border-t border-[#2a2a2a]/30 pt-4">
            <p class="text-xs text-slate-400">¿Te acordaste? <a href="login.php" class="text-[#ffb829] hover:underline font-bold">Iniciá sesión</a></p>
        </div>
    </div>

</body>
</html>
