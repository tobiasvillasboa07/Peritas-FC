<?php
/**
 * Recuperar la contraseña: el socio pone su correo y, si es socio, le llega un link
 * para elegir una contraseña nueva (ver src/models/recuperar.php).
 */
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../models/recuperar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        pedir_recuperacion($pdo, $email);
    }
    // Siempre el mismo mensaje, exista o no el correo. Volvemos con GET para que "Atrás" no reenvíe el formulario
    header('Location: olvide.php?enviado=1');
    exit;
}
$enviado = isset($_GET['enviado']);
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña - Peritas FC</title>
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
            <h1 class="text-2xl font-extrabold text-white">Recuperá tu contraseña</h1>
            <p class="text-xs text-slate-400">Poné tu correo y te mandamos un link para elegir una contraseña nueva</p>
        </div>

        <?php if ($enviado): ?>
            <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-300 text-xs rounded-xl px-4 py-3 space-y-1">
                <p>Si el correo es de un socio de Peritas FC, te mandamos un link para cambiar la contraseña.</p>
                <p>Revisá también la carpeta de spam. El link vence en <?= MINUTOS_LINK ?> minutos.</p>
            </div>
        <?php endif; ?>

        <form action="olvide.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-300 mb-1">Correo Electrónico</label>
                <input type="email" name="email" required placeholder="socio@peritas.com" class="w-full bg-slate-900 border border-[#2a2a2a] px-4 py-2.5 rounded-xl text-slate-100 focus:outline-none focus:border-[#ffb829] transition text-sm">
            </div>
            <button type="submit" class="w-full py-3 bg-gradient-to-r from-[#ffb829] to-yellow-500 text-[#000000] font-black tracking-wide rounded-xl shadow-lg hover:scale-[1.01] transition">
                <?= $enviado ? 'MANDAR OTRO LINK' : 'MANDARME EL LINK' ?>
            </button>
        </form>

        <div class="text-center border-t border-[#2a2a2a]/30 pt-4">
            <p class="text-xs text-slate-400">¿Te acordaste? <a href="login.php" class="text-[#ffb829] hover:underline font-bold">Iniciá sesión</a></p>
        </div>
    </div>

</body>
</html>
