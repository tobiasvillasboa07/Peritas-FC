<?php
require_once __DIR__ . '/../../config/bootstrap.php';
if (empty($_SESSION['user_id'])) { header('Location: /src/views/auth/login.php'); exit; }
$st = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$st->execute([$_SESSION['user_id']]);
if ($st->fetchColumn() !== 'admin') { http_response_code(403); exit('Esta sección es solo para administradores.'); }

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['accion'] ?? '') === 'borrar') {
        $pdo->prepare('DELETE FROM matches WHERE id = ?')->execute([$_POST['id'] ?? '']);   // borra también sus entradas (CASCADE)
        $msg = 'Partido eliminado.';
    } else {
        $rival = trim($_POST['away_team'] ?? '');
        $torneo = trim($_POST['tournament'] ?? '');
        $fecha = str_replace('T', ' ', $_POST['match_date'] ?? '') . ':00';
        $cupo = (int)($_POST['capacity'] ?? 0);
        if ($rival === '' || $torneo === '' || strlen($fecha) < 19 || $cupo < 1) {
            $err = 'Completá rival, torneo, fecha y un cupo mayor a 0.';
        } else {
            try {
                $pdo->prepare('INSERT INTO matches (away_team, tournament, match_date, capacity) VALUES (?, ?, ?, ?)')
                    ->execute([mb_strtoupper($rival), mb_strtoupper($torneo), $fecha, $cupo]);
                $msg = 'Partido cargado.';
            } catch (PDOException $ex) {
                $err = $ex->getCode() === '23000' ? 'Ese partido ya está cargado o los datos no son válidos.' : 'No se pudo guardar el partido.';
            }
        }
    }
}
$partidos = $pdo->query('SELECT m.*, (SELECT COUNT(*) FROM tickets t WHERE t.match_id = m.id) AS vendidas FROM matches m ORDER BY match_date DESC')->fetchAll();
$campo = 'w-full bg-slate-900 border border-[#2a2a2a] rounded-xl px-3 py-2 text-sm';
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin: partidos - Peritas FC</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">    <link rel="stylesheet" href="/assets/css/dala.css">
    <link rel="icon" type="image/png" href="/assets/img/escudo-chico.png">
</head>
<body class="bg-[#000000] text-slate-100 font-['Poppins'] min-h-screen">
<header class="bg-[#0a0a0a] border-b border-[#2a2a2a]/30"><div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
    <span class="font-extrabold text-lg"><img src="/assets/img/escudo-chico.png" alt="" class="inline-block h-9 w-auto mr-2 align-middle">PERITAS <span class="text-[#ffb829]">FC</span> · Administración</span>
    <a href="/src/views/portal.php" class="text-sm text-slate-300 hover:text-white">Volver a mi portal</a></div></header>
<main class="max-w-4xl mx-auto px-4 py-8 space-y-6">
    <h1 class="text-3xl font-black">Partidos</h1>
    <?php if ($msg): ?><div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-300 text-sm rounded-xl px-4 py-3"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="bg-red-500/10 border border-red-500/40 text-red-300 text-sm rounded-xl px-4 py-3"><?= e($err) ?></div><?php endif; ?>

    <form method="POST" class="rounded-2xl border border-[#2a2a2a]/40 bg-[#0a0a0a]/60 p-5 grid sm:grid-cols-2 gap-3">
        <input name="away_team" placeholder="Rival (ej: Deportivo Sur)" class="<?= $campo ?>" required>
        <input name="tournament" placeholder="Torneo" value="LIGA DE PRIMERA DIVISIÓN" class="<?= $campo ?>" required>
        <input type="datetime-local" name="match_date" class="<?= $campo ?>" required>
        <input type="number" name="capacity" min="1" value="500" class="<?= $campo ?>" required>
        <button class="sm:col-span-2 py-2.5 rounded-full bg-gradient-to-r from-[#ffb829] to-yellow-500 text-[#000000] font-bold text-sm">Cargar partido de local</button>
    </form>

    <div class="rounded-2xl border border-[#2a2a2a]/40 divide-y divide-[#2a2a2a]/30">
        <?php foreach ($partidos as $m): ?>
            <form method="POST" class="flex items-center justify-between gap-3 px-5 py-3 text-sm" onsubmit="return confirm('¿Eliminar este partido y sus entradas?')">
                <input type="hidden" name="accion" value="borrar"><input type="hidden" name="id" value="<?= e($m['id']) ?>">
                <span><b>vs <?= e($m['away_team']) ?></b> · <?= date('d/m/Y H:i', strtotime($m['match_date'])) ?> · <?= (int)$m['vendidas'] ?>/<?= (int)$m['capacity'] ?> entradas</span>
                <button class="text-red-400 hover:text-red-300 text-xs font-semibold">Eliminar</button>
            </form>
        <?php endforeach; ?>
        <?php if (!$partidos): ?><p class="px-5 py-4 text-slate-400 text-sm">No hay partidos cargados.</p><?php endif; ?>
    </div>
</main></body></html>
