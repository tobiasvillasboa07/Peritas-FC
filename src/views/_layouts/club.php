<?php
/**
 * Encabezado y pie compartidos de las páginas del club (plantel, resultados y administración).
 * El encabezado es el mismo que el de la página de inicio (inicio.html).
 * Uso:  club_inicio($pdo, 'Título', 'plantel');  ...contenido...  club_fin();
 */

if (!function_exists('e')) {
    function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

function club_inicio(PDO $pdo, string $titulo, string $activo = ''): void {
    global $club_usuario;
    $usuario = null;
    if (!empty($_SESSION['user_id'])) {
        $st = $pdo->prepare('SELECT name, email, role, categoria FROM users WHERE id = ?');
        $st->execute([$_SESSION['user_id']]);
        $usuario = $st->fetch() ?: null;
    }
    $club_usuario = $usuario;
    $links = [
        'inicio'     => ['INICIO', '/'],
        'noticias'   => ['NOTICIAS', '/#news'],
        'entradas'   => ['ENTRADAS', '/#tickets'],
        'plantel'    => ['PLANTEL', '/src/views/plantel.php'],
        'resultados' => ['RESULTADOS', '/src/views/resultados.php'],
        'portal'     => ['PORTAL SOCIO', '/src/views/portal.php'],
    ];
    // Barra de abajo en el celular (como en inicio)
    $linksCelular = [
        'inicio'     => ['Inicio', '/', 'fa-house'],
        'noticias'   => ['Noticias', '/#news', 'fa-newspaper'],
        'entradas'   => ['Entradas', '/#tickets', 'fa-ticket'],
        'plantel'    => ['Plantel', '/src/views/plantel.php', 'fa-people-group'],
        'resultados' => ['Resultados', '/src/views/resultados.php', 'fa-trophy'],
        'portal'     => ['Socio', '/src/views/portal.php', 'fa-id-card'],
    ];
    $admin = [
        'estadisticas'      => ['Estadísticas', '/src/views/admin/estadisticas.php'],
        'admin-partidos'    => ['Partidos', '/src/views/admin/partidos.php'],
        'admin-resultados'  => ['Resultados', '/src/views/admin/resultados.php'],
        'admin-noticias'    => ['Noticias', '/src/controllers/posts/index.php'],
    ];
    ?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> - Peritas FC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        club: { dark: '#000000', navy: '#0a0a0a', blue: '#2a2a2a', gold: '#ffb829', goldDark: '#cc8f14', accent: '#8052ff' }
                    },
                    fontFamily: { sans: ['Poppins', 'Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .active-tab { color: #ffb829 !important; border-bottom: 2px solid #ffb829; }
        /* En el celular el botón del asistente va arriba de la barra de abajo */
        @media (max-width: 767px) { #chatbot-wrapper { bottom: 5rem !important; } }
    </style>
    <link rel="stylesheet" href="/assets/css/dala.css">
    <link rel="icon" type="image/png" href="/assets/img/escudo-chico.png">
</head>
<body class="flex flex-col min-h-screen bg-club-dark text-slate-100 font-sans pb-16 md:pb-0">

<header class="sticky top-0 z-50 bg-club-navy/95 backdrop-blur-md border-b border-club-blue/30 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
        <a href="/" class="flex items-center gap-3 group">
            <img src="/assets/img/escudo-chico.png" alt="Escudo de Peritas FC" class="h-14 w-auto">
            <div>
                <span class="text-xl font-black tracking-tight text-white group-hover:text-club-gold transition">PERITAS <span class="text-club-gold">FC</span></span>
                <p class="text-[10px] text-slate-400 tracking-widest uppercase">El club de la tecnología</p>
            </div>
        </a>

        <nav class="hidden md:flex items-center gap-8 font-semibold text-sm tracking-wide text-slate-300">
            <?php foreach ($links as $clave => [$texto, $url]): ?>
                <a href="<?= $url ?>" class="hover:text-club-gold transition py-2 border-b-2 border-transparent<?= $clave === $activo ? ' active-tab' : '' ?>"><?= $texto ?></a>
            <?php endforeach; ?>
            <button type="button" onclick="openChatbotModal()" class="px-3.5 py-1.5 bg-club-gold/15 border border-club-gold/50 text-club-gold rounded-full hover:bg-club-gold hover:text-club-dark transition flex items-center gap-2 text-xs font-black shadow-sm">
                <i class="fa-solid fa-robot text-sm animate-pulse"></i> ASISTENTE
            </button>
        </nav>

        <div class="hidden md:flex items-center gap-4">
            <?php if ($usuario): ?>
                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <p class="text-sm font-semibold text-white"><?= e($usuario['name']) ?></p>
                        <p class="text-xs text-club-gold"><?= $usuario['role'] === 'admin' ? 'Administrador' : 'Socio ' . e(strtok($usuario['categoria'], ' ')) ?></p>
                    </div>
                    <a href="/src/views/portal.php" class="w-10 h-10 rounded-full border-2 border-club-gold flex items-center justify-center bg-club-blue overflow-hidden hover:scale-105 transition" title="Portal del socio">
                        <i class="fa-solid fa-user-tie text-slate-200"></i>
                    </a>
                    <a href="/src/controllers/auth/logout.php" class="p-2 text-slate-400 hover:text-red-400 transition" title="Cerrar Sesión">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </a>
                </div>
            <?php else: ?>
                <div class="flex items-center gap-3">
                    <a href="/src/views/auth/login.php" class="px-5 py-2 text-sm font-bold text-slate-300 hover:text-white transition">Iniciar Sesión</a>
                    <a href="/src/views/auth/register.php" class="px-5 py-2 text-sm font-bold bg-gradient-to-r from-club-gold to-yellow-500 text-club-dark rounded-full hover:scale-105 active:scale-95 transition shadow-lg shadow-yellow-600/20">Asociarse</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="md:hidden flex items-center">
            <span class="text-xs text-club-gold font-bold px-2 py-1 rounded bg-club-blue/40 uppercase">PERITAS Movil</span>
        </div>
    </div>
    <?php if (($usuario['role'] ?? '') === 'admin' && isset($admin[$activo])): ?>
        <div class="border-t border-[#161616]">
            <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-11 flex items-center gap-5 text-xs overflow-x-auto whitespace-nowrap">
                <span class="text-[#ffb829] font-semibold uppercase tracking-wide">Administración</span>
                <?php foreach ($admin as $clave => [$texto, $url]): ?>
                    <a href="<?= $url ?>" class="<?= $clave === $activo ? 'text-white' : 'text-slate-400 hover:text-white' ?>"><?= $texto ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    <?php endif; ?>
</header>

<!-- Barra de abajo en el celular -->
<nav class="md:hidden fixed bottom-0 left-0 w-full bg-club-navy border-t border-club-blue/50 h-16 flex items-center justify-around px-2 z-40 shadow-2xl">
    <?php foreach ($linksCelular as $clave => [$texto, $url, $icono]): ?>
        <a href="<?= $url ?>" class="flex flex-col items-center gap-1 text-slate-400 text-[11px]<?= $clave === $activo ? ' text-white' : '' ?>">
            <i class="fa-solid <?= $icono ?> text-lg"></i>
            <span><?= $texto ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">
    <?php
}

function club_fin(): void {
    global $club_usuario;
    $datos = $club_usuario ? ['name' => $club_usuario['name'], 'email' => $club_usuario['email']] : null;
    ?>
</main>
<footer class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 text-xs text-slate-500">© <?= date('Y') ?> Peritas FC · El club de la tecnología</footer>
<script>window.PFC_USER = <?= json_encode($datos, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="/assets/js/asistente.js"></script>
</body>
</html>
    <?php
}
