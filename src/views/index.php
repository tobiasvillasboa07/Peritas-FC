<?php
// Portal del socio: página a la que se llega después de iniciar sesión.
require_once __DIR__ . '/../config/bootstrap.php';

if (empty($_SESSION['user_id'])) { header('Location: /src/views/auth/login.php'); exit; }

$uid = $_SESSION['user_id'];
$st = $pdo->prepare('SELECT u.*, c.cuota_mensual FROM users u JOIN categorias c ON c.nombre = u.categoria WHERE u.id = ?');
$st->execute([$uid]);
$user = $st->fetch();
if (!$user) { session_destroy(); header('Location: /src/views/auth/login.php'); exit; }

// Genera la cuota del mes actual si todavía no existe (no repite gracias al UNIQUE)
if ($user['cuota_mensual'] > 0) {
    $pdo->prepare('INSERT OR IGNORE INTO cuotas (user_id, periodo, monto) VALUES (?, ?, ?)')
        ->execute([$uid, date('Y-m'), $user['cuota_mensual']]);
}

$cuotas = $pdo->prepare('SELECT * FROM cuotas WHERE user_id = ? ORDER BY periodo DESC LIMIT 6');
$cuotas->execute([$uid]);
$cuotas = $cuotas->fetchAll();
$pendientes = count(array_filter($cuotas, fn($c) => $c['estado'] === 'pendiente'));

$pp = $pdo->prepare('SELECT * FROM matches WHERE match_date >= ? ORDER BY match_date LIMIT 1');
$pp->execute([date('Y-m-d H:i:s')]);   // hora de Argentina, la fija bootstrap.php
$partido = $pp->fetch();
$noticias = $pdo->query('SELECT title, content, category, created_at FROM posts ORDER BY created_at DESC LIMIT 3')->fetchAll();

$te = $pdo->prepare('SELECT t.sector, t.price, m.away_team, m.match_date FROM tickets t JOIN matches m ON m.id = t.match_id WHERE t.user_id = ? ORDER BY m.match_date');
$te->execute([$uid]);
$entradas = $te->fetchAll();

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function plata($n) { return 'AR$ ' . number_format((float)$n, 0, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi portal - Peritas FC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .pitch-bg { background: linear-gradient(to right, rgba(11,19,43,.96) 35%, rgba(11,19,43,.55) 100%),
            radial-gradient(circle at 85% 15%, rgba(212,175,55,.35), transparent 45%),
            repeating-linear-gradient(90deg, #1C2541 0 70px, #17203b 70px 140px); }
        :focus-visible { outline: 2px solid #D4AF37; outline-offset: 3px; }
    </style>
</head>
<body class="bg-[#0B132B] text-slate-100 font-['Poppins'] min-h-screen">

<header class="bg-[#1C2541]/95 border-b border-[#3A506B]/30 sticky top-0 z-50">
    <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
        <a href="/" class="font-extrabold text-lg">PERITAS <span class="text-[#D4AF37]">FC</span></a>
        <div class="flex items-center gap-4 text-sm">
            <a href="/" class="text-slate-300 hover:text-white hidden sm:block">Inicio</a>
            <a href="/src/views/entradas.php" class="text-slate-300 hover:text-white">Entradas</a>
            <?php if ($user['role'] === 'admin'): ?>
                <a href="/src/views/admin/partidos.php" class="text-[#D4AF37] hover:text-yellow-300">Partidos</a>
                <a href="/src/controllers/posts/index.php" class="text-[#D4AF37] hover:text-yellow-300">Noticias</a>
            <?php endif; ?>
            <span class="text-right leading-tight"><span class="block font-semibold"><?= e($user['name']) ?></span><span class="block text-xs text-[#D4AF37]"><?= $user['role'] === 'admin' ? 'Administrador' : 'Socio ' . e(strtok($user['categoria'], ' ')) ?></span></span>
            <a href="/src/views/auth/logout.php" class="px-3 py-1.5 rounded-full border border-[#3A506B] hover:border-[#D4AF37] text-xs font-semibold">Salir</a>
        </div>
    </div>
</header>

<main class="max-w-6xl mx-auto px-4 py-8 space-y-10">

    <!-- Próximo partido -->
    <section class="pitch-bg rounded-3xl border border-[#3A506B]/50 px-6 sm:px-12 py-12">
        <?php if ($partido): ?>
            <p class="text-sm text-[#D4AF37] font-semibold mb-3"><?= e($partido['tournament']) ?> · <?= date('d/m/Y H:i', strtotime($partido['match_date'])) ?> hs</p>
            <h1 class="font-black text-4xl sm:text-6xl leading-none"><?= e($partido['home_team']) ?><span class="block text-[#D4AF37] text-xl my-2">vs</span><span class="text-slate-200"><?= e($partido['away_team']) ?></span></h1>
            <p class="text-slate-400 text-sm mt-4"><?= e($partido['stadium']) ?></p>
            <div id="cuenta" data-fecha="<?= e(str_replace(' ', 'T', $partido['match_date'])) ?>" class="flex gap-3 mt-6">
                <?php foreach (['d' => 'Días', 'h' => 'Horas', 'm' => 'Min'] as $k => $l): ?>
                    <div class="bg-slate-900/80 border border-[#3A506B]/50 rounded-xl px-4 py-3 text-center min-w-[72px]"><span id="c-<?= $k ?>" class="block text-2xl font-bold">--</span><span class="text-xs text-slate-400"><?= $l ?></span></div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <h1 class="font-black text-3xl">Todavía no hay partidos cargados</h1>
            <p class="text-slate-400 mt-2 text-sm">Cuando el club publique el próximo partido, lo vas a ver acá.</p>
        <?php endif; ?>
    </section>

    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Credencial -->
        <section class="space-y-4">
            <h2 class="text-xl font-bold">Mi credencial</h2>
            <div class="rounded-2xl p-6 bg-gradient-to-br from-[#1C2541] to-[#0B132B] border border-[#D4AF37]/40 space-y-4">
                <p class="text-xs text-[#D4AF37] font-bold">PERITAS FC · CLUB DE SOCIOS</p>
                <p class="text-2xl font-extrabold"><?= e($user['name']) ?></p>
                <dl class="text-sm space-y-1 text-slate-300">
                    <div class="flex justify-between"><dt class="text-slate-400">Categoría</dt><dd><?= e($user['categoria']) ?></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-400">DNI</dt><dd><?= e($user['dni'] ?: '—') ?></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-400">Socio desde</dt><dd><?= date('d/m/Y', strtotime($user['created_at'])) ?></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-400">Estado</dt><dd class="<?= $pendientes ? 'text-yellow-400' : 'text-emerald-400' ?>"><?= $pendientes ? 'Cuota pendiente' : 'Al día' ?></dd></div>
                </dl>
            </div>
        </section>

        <!-- Cuotas -->
        <section class="lg:col-span-2 space-y-4">
            <h2 class="text-xl font-bold">Mis cuotas</h2>
            <div class="rounded-2xl border border-[#3A506B]/40 bg-[#1C2541]/60 divide-y divide-[#3A506B]/30">
                <?php foreach ($cuotas as $c): ?>
                    <div class="flex items-center justify-between px-5 py-3 text-sm">
                        <span>Cuota <?= e($c['periodo']) ?></span>
                        <span class="font-semibold"><?= plata($c['monto']) ?></span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold <?= $c['estado'] === 'pagada' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-yellow-500/20 text-yellow-300' ?>"><?= e($c['estado']) ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if (!$cuotas): ?><p class="px-5 py-4 text-sm text-slate-400">Tu categoría no tiene cuota mensual.</p><?php endif; ?>
            </div>
        </section>
    </div>

    <!-- Entradas -->
    <section class="space-y-4">
        <h2 class="text-xl font-bold">Mis entradas</h2>
        <?php if ($entradas): ?>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($entradas as $t): ?>
                    <div class="rounded-2xl border border-[#3A506B]/40 bg-[#1C2541]/60 p-4 text-sm"><p class="font-bold">vs <?= e($t['away_team']) ?></p><p class="text-slate-400"><?= date('d/m/Y H:i', strtotime($t['match_date'])) ?> · <?= e($t['sector']) ?></p><p class="text-[#D4AF37] font-semibold mt-1"><?= plata($t['price']) ?></p></div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-sm text-slate-400">Todavía no sacaste entradas. <a href="/src/views/entradas.php" class="text-[#D4AF37] font-semibold hover:underline">Sacá la primera</a>.</p>
        <?php endif; ?>
    </section>

    <!-- Noticias -->
    <section class="space-y-4">
        <h2 class="text-xl font-bold">Últimas novedades</h2>
        <?php if ($noticias): ?>
            <div class="grid md:grid-cols-3 gap-4">
                <?php foreach ($noticias as $n): ?>
                    <article class="rounded-2xl border border-[#3A506B]/40 bg-[#1C2541]/60 p-5"><p class="text-xs text-[#D4AF37] font-semibold"><?= e($n['category']) ?> · <?= date('d/m/Y', strtotime($n['created_at'])) ?></p><h3 class="font-bold mt-1"><?= e($n['title']) ?></h3><p class="text-sm text-slate-400 mt-2"><?= e(mb_strimwidth($n['content'], 0, 140, '…')) ?></p></article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-sm text-slate-400">El club todavía no publicó noticias.</p>
        <?php endif; ?>
    </section>
</main>

<script>
    const box = document.getElementById('cuenta');
    if (box) {
        const fin = new Date(box.dataset.fecha);
        const poner = () => {
            const ms = Math.max(0, fin - new Date());
            document.getElementById('c-d').textContent = String(Math.floor(ms / 864e5)).padStart(2, '0');
            document.getElementById('c-h').textContent = String(Math.floor(ms % 864e5 / 36e5)).padStart(2, '0');
            document.getElementById('c-m').textContent = String(Math.floor(ms % 36e5 / 6e4)).padStart(2, '0');
        };
        poner(); setInterval(poner, 30000);
    }
</script>
</body>
</html>