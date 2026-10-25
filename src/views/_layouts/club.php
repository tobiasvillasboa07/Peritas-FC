<?php
/**
 * Encabezado y pie compartidos de las páginas del club (plantel, resultados y administración).
 * Uso:  club_inicio($pdo, 'Título', 'plantel');  ...contenido...  club_fin();
 */

if (!function_exists('e')) {
    function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

function club_inicio(PDO $pdo, string $titulo, string $activo = ''): void {
    $usuario = null;
    if (!empty($_SESSION['user_id'])) {
        $st = $pdo->prepare('SELECT name, role FROM users WHERE id = ?');
        $st->execute([$_SESSION['user_id']]);
        $usuario = $st->fetch() ?: null;
    }
    $links = [
        'inicio'     => ['INICIO', '/'],
        'noticias'   => ['NOTICIAS', '/#news'],
        'entradas'   => ['ENTRADAS', '/#tickets'],
        'plantel'    => ['PLANTEL', '/src/views/plantel.php'],
        'resultados' => ['RESULTADOS', '/src/views/resultados.php'],
        'portal'     => ['PORTAL SOCIO', '/src/views/index.php'],
    ];
    $admin = [
        'estadisticas'      => ['Estadísticas', '/src/views/admin/estadisticas.php'],
        'admin-partidos'    => ['Partidos', '/src/views/admin/partidos.php'],
        'admin-resultados'  => ['Resultados', '/src/views/admin/resultados.php'],
        'admin-noticias'    => ['Noticias', '/src/controllers/posts/index.php'],
    ];
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> - Peritas FC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/dala.css">
    <link rel="icon" type="image/png" href="/assets/img/escudo-chico.png">
</head>
<body class="bg-black text-slate-100 min-h-screen">
<header class="border-b border-[#161616] sticky top-0 z-40">
    <div class="max-w-6xl mx-auto px-4 h-20 flex items-center justify-between gap-6">
        <a href="/" class="flex items-center gap-3 shrink-0">
            <img src="/assets/img/escudo-chico.png" alt="Escudo de Peritas FC" class="h-14 w-auto">
            <span class="hidden sm:block text-lg font-semibold">PERITAS <span class="text-[#ffb829]">FC</span></span>
        </a>
        <nav class="flex items-center gap-6 overflow-x-auto text-xs sm:text-sm font-semibold whitespace-nowrap">
            <?php foreach ($links as $clave => [$texto, $url]): ?>
                <a href="<?= $url ?>" class="py-2 border-b-2 <?= $clave === $activo ? 'active-tab' : 'border-transparent' ?>"><?= $texto ?></a>
            <?php endforeach; ?>
        </nav>
        <?php if ($usuario): ?>
            <a href="/src/views/auth/logout.php" class="hidden md:block shrink-0 text-xs text-slate-400 hover:text-white"><?= e($usuario['name']) ?> · Salir</a>
        <?php else: ?>
            <a href="/src/views/auth/login.php" class="hidden md:block shrink-0 text-xs text-[#ffb829] hover:underline">Iniciar sesión</a>
        <?php endif; ?>
    </div>
    <?php if (($usuario['role'] ?? '') === 'admin' && isset($admin[$activo])): ?>
        <div class="border-t border-[#161616]">
            <nav class="max-w-6xl mx-auto px-4 h-11 flex items-center gap-5 text-xs overflow-x-auto whitespace-nowrap">
                <span class="text-[#ffb829] font-semibold uppercase tracking-wide">Administración</span>
                <?php foreach ($admin as $clave => [$texto, $url]): ?>
                    <a href="<?= $url ?>" class="<?= $clave === $activo ? 'text-white' : 'text-slate-400 hover:text-white' ?>"><?= $texto ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    <?php endif; ?>
</header>
<main class="max-w-6xl mx-auto px-4 py-10 space-y-10">
    <?php
}

function club_fin(): void {
    ?>
</main>
<footer class="max-w-6xl mx-auto px-4 py-10 text-xs text-slate-500">© <?= date('Y') ?> Peritas FC · El club de la tecnología</footer>
</body>
</html>
    <?php
}