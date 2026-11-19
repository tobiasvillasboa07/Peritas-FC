<?php
// Portal del socio: página a la que se llega después de iniciar sesión.
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/mercadopago.php';
require_once __DIR__ . '/../models/foto_socio.php';

if (empty($_SESSION['user_id'])) { header('Location: /src/views/auth/login.php'); exit; }

$uid = $_SESSION['user_id'];
// rowid es el número de fila de SQLite (1, 2, 3...): lo usamos como número de socio
$st = $pdo->prepare('SELECT u.*, u.rowid AS numero_socio, c.cuota_mensual FROM users u JOIN categorias c ON c.nombre = u.categoria WHERE u.id = ?');
$st->execute([$uid]);
$user = $st->fetch();
if (!$user) { session_destroy(); header('Location: /src/views/auth/login.php'); exit; }

    // Genera la cuota del mes actual como pendiente si todavía no existe
generar_cuota_mes($pdo, $uid);

// Pagar una cuota con Mercado Pago (el botón "Pagar" manda el id de la cuota)
$errorPago = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cuota_id'])) {
    try {
        header('Location: ' . crear_pago_cuota($pdo, $uid, $_POST['cuota_id']));
        exit;
    } catch (InvalidArgumentException $ex) {
        $errorPago = $ex->getMessage();
    } catch (Throwable $ex) {
        error_log('Error creando el pago de la cuota: ' . $ex->getMessage());
        $errorPago = 'No se pudo iniciar el pago con Mercado Pago. Intentá de nuevo.';
    }
}
$avisosPago = [
    'aprobado'  => ['¡Cuota pagada! Ya estás al día.', 'emerald'],
    'pendiente' => ['Tu pago está pendiente. La cuota se marca como pagada cuando Mercado Pago lo acredite.', 'yellow'],
    'rechazado' => ['El pago no se completó. Podés intentar de nuevo.', 'red'],
    'error'     => ['No pudimos verificar el pago con Mercado Pago.', 'red'],
];
$avisoPago = $errorPago ? [$errorPago, 'red'] : ($avisosPago[$_GET['pago'] ?? ''] ?? null);

$cuotas = $pdo->prepare('SELECT * FROM cuotas WHERE user_id = ? ORDER BY periodo DESC LIMIT 6');
$cuotas->execute([$uid]);
$cuotas = $cuotas->fetchAll();
$pendientes = count(array_filter($cuotas, fn($c) => $c['estado'] === 'pendiente'));

// Credencial: foto del socio (o null) y el error si la última foto que subió no servía
$foto = foto_socio($pdo, $uid);
$errorFoto = $_SESSION['foto_error'] ?? '';
unset($_SESSION['foto_error']);
$meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
$desde = strtotime($user['created_at']);

$pp = $pdo->prepare('SELECT * FROM matches WHERE match_date >= ? ORDER BY match_date LIMIT 1');
$pp->execute([date('Y-m-d H:i:s')]);   // hora de Argentina, la fija bootstrap.php
$partido = $pp->fetch();
$noticias = $pdo->query('SELECT title, content, category, created_at FROM posts ORDER BY created_at DESC LIMIT 3')->fetchAll();

$te = $pdo->prepare('SELECT t.sector, t.price, m.away_team, m.match_date FROM tickets t JOIN matches m ON m.id = t.match_id WHERE t.user_id = ? ORDER BY m.match_date');
$te->execute([$uid]);
$entradas = $te->fetchAll();

// Historial de pagos con Mercado Pago: entradas y cuotas (aprobados y rechazados)
$hp = $pdo->prepare("
    SELECT p.created_at AS fecha, 'Entrada vs ' || m.away_team AS concepto, p.sector AS sector, p.monto, p.estado
      FROM pagos p JOIN matches m ON m.id = p.match_id
     WHERE p.user_id = ? AND p.estado <> 'pendiente'
    UNION ALL
    SELECT pc.created_at, 'Cuota social ' || c.periodo, '', pc.monto, pc.estado
      FROM pagos_cuotas pc JOIN cuotas c ON c.id = pc.cuota_id
     WHERE pc.user_id = ? AND pc.estado <> 'pendiente'
    ORDER BY fecha DESC");
$hp->execute([$uid, $uid]);
$historial = $hp->fetchAll();
// e(): escapa texto (contra XSS) · plata(): formatea pesos (AR$ 5.000)
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
            repeating-linear-gradient(90deg, #0a0a0a 0 70px, #17203b 70px 140px); }
        :focus-visible { outline: 2px solid #ffb829; outline-offset: 3px; }
        /* En el celular el botón del asistente va arriba de la barra de abajo */
        @media (max-width: 767px) { #chatbot-wrapper { bottom: 5rem !important; } }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/dala.css">
    <link rel="icon" type="image/png" href="/assets/img/escudo-chico.png">
</head>
<body class="bg-[#000000] text-slate-100 font-['Poppins'] min-h-screen pb-16 md:pb-0">

 <header class="bg-[#0a0a0a]/95 backdrop-blur-md border-b border-[#2a2a2a]/30 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 h-20 flex items-center justify-between">
        <a href="/" class="flex items-center gap-3 group">
            <img src="/assets/img/escudo-chico.png" alt="Escudo de Peritas FC" class="h-14 w-auto">
            <div>
                <span class="text-xl font-black tracking-tight text-white">PERITAS <span class="text-[#ffb829]">FC</span></span>
                <p class="text-[10px] text-slate-400 tracking-widest uppercase">El club de la tecnología</p>
            </div>
        </a>

        <nav class="hidden md:flex items-center gap-8 font-semibold text-sm tracking-wide text-slate-300">
            <a href="/" class="hover:text-[#ffb829] transition py-2">INICIO</a>
            <a href="/#news" class="hover:text-[#ffb829] transition py-2">NOTICIAS</a>
            <a href="/src/views/entradas.php" class="hover:text-[#ffb829] transition py-2">ENTRADAS</a>
            <a href="/src/views/plantel.php" class="hover:text-[#ffb829] transition py-2">PLANTEL</a>
            <a href="/src/views/resultados.php" class="hover:text-[#ffb829] transition py-2">RESULTADOS</a>
            <a href="/src/views/portal.php" class="text-[#ffb829] py-2 border-b-2 border-[#ffb829]">PORTAL SOCIO</a>
            <?php if ($user['role'] === 'admin'): ?>
                <a href="/src/views/admin/estadisticas.php" class="text-[#ffb829] hover:text-yellow-300 transition py-2">ADMIN</a>
            <?php endif; ?>
            <button type="button" onclick="window.openChatbotModal && openChatbotModal()"
                class="px-3.5 py-1.5 bg-[#ffb829]/15 border border-[#ffb829]/50 text-[#ffb829] rounded-full hover:bg-[#ffb829] hover:text-[#000000] transition text-xs font-black">
                                <i class="fa-solid fa-robot text-sm animate-pulse"></i> ASISTENTE
            </button>
        </nav>

        <div class="flex items-center gap-4 text-sm">
            <span class="text-right leading-tight">
                <span class="block font-semibold"><?= e($user['name']) ?></span>
                <span class="block text-xs text-[#ffb829]"><?= $user['role'] === 'admin' ? 'Administrador' : 'Socio ' . e(strtok($user['categoria'], ' ')) ?></span>
            </span>
            <a href="/src/controllers/auth/logout.php" class="px-3 py-1.5 rounded-full border border-[#2a2a2a] hover:border-[#ffb829] text-xs font-semibold">Salir</a>
        </div>
    </div>
    <?php if ($user['role'] === 'admin'): ?>
        <!-- En el celular el menú de arriba no se ve: el admin entra a su panel desde acá -->
        <div class="md:hidden border-t border-[#161616] px-4 h-10 flex items-center text-xs">
            <a href="/src/views/admin/estadisticas.php" class="text-[#ffb829] font-semibold">Panel de administración →</a>
        </div>
    <?php endif; ?>
</header>

<!-- Barra de abajo en el celular (como en inicio, plantel y resultados) -->
<nav class="md:hidden fixed bottom-0 left-0 w-full bg-[#0a0a0a] border-t border-[#2a2a2a]/50 h-16 flex items-center justify-around px-2 z-40 shadow-2xl">
    <?php foreach ([
        ['Inicio', '/', 'fa-house'],
        ['Noticias', '/#news', 'fa-newspaper'],
        ['Entradas', '/src/views/entradas.php', 'fa-ticket'],
        ['Plantel', '/src/views/plantel.php', 'fa-people-group'],
        ['Resultados', '/src/views/resultados.php', 'fa-trophy'],
        ['Socio', '/src/views/portal.php', 'fa-id-card'],
    ] as [$texto, $url, $icono]): ?>
        <a href="<?= $url ?>" class="flex flex-col items-center gap-1 text-[11px] <?= $texto === 'Socio' ? 'text-[#ffb829]' : 'text-slate-400' ?>">
            <i class="fa-solid <?= $icono ?> text-lg"></i>
            <span><?= $texto ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<main class="max-w-6xl mx-auto px-4 py-8 space-y-10">

    <!-- Próximo partido -->
    <?php $fondo = $partido ? foto_partido($partido['away_team']) : null;   // foto del rival (assets/img/partidos) ?>
    <section class="<?= $fondo ? '' : 'pitch-bg' ?> rounded-3xl border border-[#2a2a2a]/50 px-6 sm:px-12 py-12"<?php if ($fondo): ?> style="background: linear-gradient(to right, rgba(0,0,0,.92) 35%, rgba(0,0,0,.45) 100%), url('<?= e($fondo) ?>') center / cover"<?php endif; ?>>
        <?php if ($partido): ?>
            <p class="text-sm text-[#ffb829] font-semibold mb-3"><?= e($partido['tournament']) ?> · <?= date('d/m/Y H:i', strtotime($partido['match_date'])) ?> hs</p>
            <h1 class="font-black text-4xl sm:text-6xl leading-none"><?= e($partido['home_team']) ?><span class="block text-[#ffb829] text-xl my-2">vs</span><span class="text-slate-200"><?= e($partido['away_team']) ?></span></h1>
            <p class="text-slate-400 text-sm mt-4"><?= e($partido['stadium']) ?></p>
            <div id="cuenta" data-fecha="<?= e(str_replace(' ', 'T', $partido['match_date'])) ?>" class="flex gap-3 mt-6">
                <?php foreach (['d' => 'Días', 'h' => 'Horas', 'm' => 'Min'] as $k => $l): ?>
                    <div class="bg-slate-900/80 border border-[#2a2a2a]/50 rounded-xl px-4 py-3 text-center min-w-[72px]"><span id="c-<?= $k ?>" class="block text-2xl font-bold">--</span><span class="text-xs text-slate-400"><?= $l ?></span></div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <h1 class="font-black text-3xl">Todavía no hay partidos cargados</h1>
            <p class="text-slate-400 mt-2 text-sm">Cuando el club publique el próximo partido, lo vas a ver acá.</p>
        <?php endif; ?>
    </section>

    <!-- Credencial del socio (carnet) con su foto a la derecha -->
    <section id="credencial" class="space-y-4">
        <h2 class="text-xl font-bold">Mi credencial</h2>
        <div class="relative overflow-hidden max-w-2xl rounded-2xl border border-[#8052ff]/40 bg-gradient-to-br from-[#1c1238] via-[#0d0a18] to-[#000000] p-6 sm:p-8">
            <!-- Adornos de fondo: un círculo y las letras PFC -->
            <div class="absolute -top-20 -right-20 w-64 h-64 rounded-full bg-[#8052ff]/15"></div>
            <span class="absolute left-1/2 -bottom-4 text-8xl font-black text-white/5 select-none pointer-events-none">PFC</span>

            <div class="relative flex items-start justify-between gap-5">
                <div class="space-y-3 min-w-0">
                    <p class="text-xs tracking-[0.2em] text-[#b9a3ff]">N° DE SOCIO: <?= str_pad($user['numero_socio'], 6, '0', STR_PAD_LEFT) ?></p>
                    <p class="text-2xl sm:text-3xl font-extrabold leading-tight break-words"><?= e($user['name']) ?></p>
                    <?php if ($pendientes): ?>
                        <span class="inline-block px-3 py-1 rounded-full bg-yellow-500 text-[#000000] text-xs font-black tracking-widest">CUOTA PENDIENTE</span>
                    <?php else: ?>
                        <span class="inline-block px-3 py-1 rounded-full bg-emerald-600 text-white text-xs font-black tracking-widest">SOCIO ACTIVO</span>
                    <?php endif; ?>
                    <dl class="flex flex-wrap gap-x-8 gap-y-3 pt-2">
                        <div><dt class="text-[11px] tracking-widest uppercase text-[#b9a3ff]">Categoría</dt><dd class="font-bold"><?= e(strtok($user['categoria'], ' ')) ?></dd></div>
                        <div><dt class="text-[11px] tracking-widest uppercase text-[#b9a3ff]">Desde</dt><dd class="font-bold"><?= $meses[date('n', $desde) - 1] . ' ' . date('Y', $desde) ?></dd></div>
                        <div><dt class="text-[11px] tracking-widest uppercase text-[#b9a3ff]">DNI</dt><dd class="font-bold"><?= $user['dni'] ? number_format((int)$user['dni'], 0, ',', '.') : '—' ?></dd></div>
                    </dl>
                </div>

                <!-- Foto del socio: se sube desde acá (src/controllers/socios/foto.php) -->
                <div class="shrink-0 flex flex-col items-center gap-2">
                    <div class="w-24 h-32 sm:w-28 sm:h-36 rounded-xl overflow-hidden border-2 border-[#ffb829]/70 bg-[#141414] flex items-center justify-center">
                        <?php if ($foto): ?>
                            <img src="<?= e($foto) ?>" alt="Foto de <?= e($user['name']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fa-solid fa-user text-4xl text-slate-600"></i>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-3 text-xs">
                        <form action="/src/controllers/socios/foto.php" method="POST" enctype="multipart/form-data">
                            <label class="cursor-pointer font-semibold text-[#ffb829] hover:underline">
                                <?= $foto ? 'Cambiar' : 'Agregar foto' ?>
                                <input type="file" name="foto" id="foto-input" accept="image/jpeg,image/png,image/webp" class="hidden">
                            </label>
                        </form>
                        <?php if ($foto): ?>
                            <form action="/src/controllers/socios/foto.php" method="POST">
                                <button type="submit" name="borrar" value="1" class="text-slate-400 hover:text-red-400">Quitar</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($errorFoto): ?>
            <p class="text-sm text-red-300"><?= e($errorFoto) ?></p>
        <?php endif; ?>
    </section>

    <!-- Cuotas -->
    <section id="cuotas" class="space-y-4">
        <h2 class="text-xl font-bold">Mis cuotas</h2>
        <?php if ($avisoPago): ?>
            <div class="rounded-xl px-4 py-3 text-sm border bg-<?= $avisoPago[1] ?>-500/10 border-<?= $avisoPago[1] ?>-500/40 text-<?= $avisoPago[1] ?>-300"><?= e($avisoPago[0]) ?></div>
        <?php endif; ?>
        <?php if ($pendientes): ?>
            <p class="text-sm text-yellow-300">Tenés <?= $pendientes ?> cuota<?= $pendientes > 1 ? 's' : '' ?> sin pagar. Hasta que estés al día no tenés los beneficios de socio (Tribuna Popular gratis).</p>
        <?php endif; ?>
        <div class="rounded-2xl border border-[#2a2a2a]/40 bg-[#0a0a0a]/60 divide-y divide-[#2a2a2a]/30">
            <?php foreach ($cuotas as $c): ?>
                <div class="flex items-center justify-between px-5 py-3 text-sm">
                    <span>Cuota <?= e($c['periodo']) ?></span>
                    <span class="font-semibold"><?= plata($c['monto']) ?></span>
                                            <span class="px-2 py-0.5 rounded-full text-xs font-bold <?= $c['estado'] === 'pagada' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-yellow-500/20 text-yellow-300' ?>"><?= e($c['estado']) ?></span>
                    <?php if ($c['estado'] === 'pendiente'): ?>
                        <form method="POST"><input type="hidden" name="cuota_id" value="<?= e($c['id']) ?>">
                            <button class="px-4 py-1.5 rounded-full bg-gradient-to-r from-[#ffb829] to-yellow-500 text-[#000000] font-bold text-xs hover:scale-105 transition">Pagar</button></form>
                    <?php else: ?>
                        <span class="w-[68px]"></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if (!$cuotas): ?><p class="px-5 py-4 text-sm text-slate-400">Tu categoría no tiene cuota mensual.</p><?php endif; ?>
        </div>
    </section>

    <!-- Entradas -->
    <section class="space-y-4">
        <h2 class="text-xl font-bold">Mis entradas</h2>
        <?php if ($entradas): ?>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($entradas as $t): ?>
                    <div class="rounded-2xl border border-[#2a2a2a]/40 bg-[#0a0a0a]/60 p-4 text-sm"><p class="font-bold">vs <?= e($t['away_team']) ?></p><p class="text-slate-400"><?= date('d/m/Y H:i', strtotime($t['match_date'])) ?> · <?= e($t['sector']) ?></p><p class="text-[#ffb829] font-semibold mt-1"><?= plata($t['price']) ?></p></div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-sm text-slate-400">Todavía no sacaste entradas. <a href="/src/views/entradas.php" class="text-[#ffb829] font-semibold hover:underline">Sacá la primera</a>.</p>
        <?php endif; ?>
    </section>

        <!-- Historial de pagos -->
    <section id="historial" class="space-y-4">
        <h2 class="text-xl font-bold">Historial de pagos</h2>
        <?php if ($historial): ?>
            <div class="rounded-2xl border border-[#2a2a2a]/40 bg-[#0a0a0a]/60 divide-y divide-[#2a2a2a]/30">
                <?php foreach ($historial as $h): $ok = $h['estado'] === 'aprobado'; ?>
                    <div class="grid grid-cols-[1fr_auto] sm:grid-cols-[9rem_1fr_auto_auto] items-center gap-x-4 gap-y-1 px-5 py-3 text-sm">
                        <span class="text-slate-400 tabular-nums"><?= date('d/m/Y H:i', strtotime($h['fecha'] . ' UTC')) ?></span>
                        <span class="order-first sm:order-none col-span-2 sm:col-span-1"><?= e($h['concepto']) ?><?php if ($h['sector'] !== ''): ?> <span class="text-slate-400">· <?= e(SECTORES[$h['sector']][0] ?? $h['sector']) ?></span><?php endif; ?></span>
                        <span class="font-semibold tabular-nums text-right"><?= plata($h['monto']) ?></span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold text-center <?= $ok ? 'bg-emerald-500/20 text-emerald-300' : 'bg-red-500/20 text-red-300' ?>"><?= $ok ? 'Aprobado' : 'Rechazado' ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-sm text-slate-400">Todavía no hiciste pagos con Mercado Pago. Cuando pagues una entrada o una cuota, va a aparecer acá.</p>
        <?php endif; ?>
    </section>

    <!-- Noticias -->
    <section class="space-y-4">
        <h2 class="text-xl font-bold">Últimas novedades</h2>
        <?php if ($noticias): ?>
            <div class="grid md:grid-cols-3 gap-4">
                <?php foreach ($noticias as $n): ?>
                    <article class="rounded-2xl border border-[#2a2a2a]/40 bg-[#0a0a0a]/60 p-5"><p class="text-xs text-[#ffb829] font-semibold"><?= e($n['category']) ?> · <?= date('d/m/Y', strtotime($n['created_at'])) ?></p><h3 class="font-bold mt-1"><?= e($n['title']) ?></h3><p class="text-sm text-slate-400 mt-2"><?= e(mb_strimwidth($n['content'], 0, 140, '…')) ?></p></article>
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
        const poner = () => {   // cuenta regresiva del próximo partido (se repite cada 30 s)
            const ms = Math.max(0, fin - new Date());
            document.getElementById('c-d').textContent = String(Math.floor(ms / 864e5)).padStart(2, '0');
            document.getElementById('c-h').textContent = String(Math.floor(ms % 864e5 / 36e5)).padStart(2, '0');
            document.getElementById('c-m').textContent = String(Math.floor(ms % 36e5 / 6e4)).padStart(2, '0');
        };
        poner(); setInterval(poner, 30000);
    }

    // Al elegir la foto: la achica a 400 px (canvas), la pasa a JPG y manda el formulario a socios/foto.php
    const inputFoto = document.getElementById('foto-input');
    inputFoto.addEventListener('change', async () => {
        const archivo = inputFoto.files[0];
        if (!archivo) return;
        try {
            const imagen = await createImageBitmap(archivo);
            const escala = Math.min(1, 400 / imagen.width);
            const lienzo = document.createElement('canvas');
            lienzo.width = Math.round(imagen.width * escala);
            lienzo.height = Math.round(imagen.height * escala);
            lienzo.getContext('2d').drawImage(imagen, 0, 0, lienzo.width, lienzo.height);
            const jpg = await new Promise(listo => lienzo.toBlob(listo, 'image/jpeg', 0.85));
            const lista = new DataTransfer();
            lista.items.add(new File([jpg], 'foto.jpg', { type: 'image/jpeg' }));
            inputFoto.files = lista.files;
        } catch (error) {
            // Si el navegador no la puede achicar, se sube como está (el servidor igual la revisa)
        }
        inputFoto.form.submit();
    });
</script>
<script>window.PFC_USER = <?= json_encode(['name' => $user['name'], 'email' => $user['email']], JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="/assets/js/asistente.js"></script>
<script>
    // pageshow: si "Atrás" muestra el portal guardado en memoria, lo recarga para que el servidor revise la sesión

    window.addEventListener('pageshow', e => { if (e.persisted) location.reload(); });
</script>
</body>
</html>