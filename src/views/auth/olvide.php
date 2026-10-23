<?php
/**
 * Recuperar la contraseña: el socio confirma su correo y su DNI y elige una contraseña nueva.
 * (Una página real mandaría un link por mail; acá usamos el DNI como dato de verificación.)
 */
require_once __DIR__ . '/../../config/bootstrap.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $dni      = trim($_POST['dni'] ?? '');
    $password = $_POST['password'] ?? '';
    $repetir  = $_POST['repetir'] ?? '';

    if (strlen($password) < 8) {
        $error = 'La contraseña nueva debe tener al menos 8 caracteres.';
    } elseif ($password !== $repetir) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $st = $pdo->prepare('SELECT id FROM users WHERE lower(email) = ? AND dni = ?');
        $st->execute([$email, $dni]);
        $id = $st->fetchColumn();
        if ($id) {
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
            header('Location: login.php?restablecida=1');
            exit;
        }
        $error = 'El correo y el DNI no coinciden con ningún socio.';
    }
}
$campo = 'w-full bg-slate-900 border border-[#3A506B] px-4 py-2.5 rounded-xl text-slate-100 focus:outline-none focus:border-[#D4AF37] transition text-sm';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña - Peritas FC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="flex flex-col min-h-screen bg-[#0B132B] text-slate-100 font-['Poppins'] justify-center items-center p-4">

    <div class="max-w-md w-full bg-[#1C2541] border border-[#3A506B]/50 rounded-2xl p-8 space-y-6 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-[#D4AF37] to-yellow-500"></div>

        <div class="text-center space-y-2">
            <h1 class="text-2xl font-extrabold text-white">Recuperá tu contraseña</h1>
            <p class="text-xs text-slate-400">Confirmá tu correo y tu DNI y elegí una contraseña nueva</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/40 text-red-300 text-xs rounded-xl px-4 py-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="olvide.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-300 mb-1">Correo Electrónico</label>
                <input type="email" name="email" required placeholder="socio@peritas.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" class="<?= $campo ?>">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-300 mb-1">DNI</label>
                <input type="text" name="dni" required inputmode="numeric" maxlength="8" placeholder="Ej: 45123987" value="<?= htmlspecialchars($_POST['dni'] ?? '') ?>" class="<?= $campo ?>">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-300 mb-1">Contraseña nueva</label>
                <input type="password" name="password" required minlength="8" placeholder="••••••••" class="<?= $campo ?>">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-300 mb-1">Repetir contraseña</label>
                <input type="password" name="repetir" required minlength="8" placeholder="••••••••" class="<?= $campo ?>">
            </div>
            <button type="submit" class="w-full py-3 bg-gradient-to-r from-[#D4AF37] to-yellow-500 text-[#0B132B] font-black tracking-wide rounded-xl shadow-lg hover:scale-[1.01] transition">
                CAMBIAR CONTRASEÑA
            </button>
        </form>

        <div class="text-center border-t border-[#3A506B]/30 pt-4">
            <p class="text-xs text-slate-400">¿Te acordaste? <a href="login.php" class="text-[#D4AF37] hover:underline font-bold">Iniciá sesión</a></p>
        </div>
    </div>

</body>
</html>