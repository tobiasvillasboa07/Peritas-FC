<?php
// Administración: cargar y borrar resultados (solo admin)
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/admin.php';
require_once __DIR__ . '/../../models/resultados.php';
require_once __DIR__ . '/../_layouts/club.php';
requerir_admin($pdo);
preparar_resultados($pdo);

$msg = $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['accion'] ?? '') === 'borrar') {
        $pdo->prepare('DELETE FROM resultados WHERE id = ?')->execute([$_POST['id'] ?? '']);
        $msg = 'Resultado eliminado.';
    } else {
        try {
            guardar_resultado($pdo, $_POST['torneo'] ?? '', $_POST['fecha'] ?? '', $_POST['local'] ?? '', $_POST['visitante'] ?? '',
                              $_POST['goles_local'] ?? '', $_POST['goles_visitante'] ?? '');
            $msg = 'Resultado cargado.';
        } catch (InvalidArgumentException $ex) {
            $err = $ex->getMessage();
        }
    }
}

// Partidos de Peritas que ya se jugaron, para cargar su resultado más rápido
$jugados = $pdo->prepare("SELECT m.* FROM matches m WHERE m.match_date < ? AND NOT EXISTS (
    SELECT 1 FROM resultados r WHERE r.fecha = date(m.match_date) AND r.local = m.home_team AND r.visitante = m.away_team)
    ORDER BY m.match_date DESC");
$jugados->execute([date('Y-m-d H:i:s')]);
$jugados = $jugados->fetchAll();
$resultados = $pdo->query('SELECT * FROM resultados ORDER BY fecha DESC, id DESC')->fetchAll();
$campo = 'bg-slate-900 border border-[#2a2a2a] rounded-xl px-3 py-2 text-sm w-full';

club_inicio($pdo, 'Resultados · Administración', 'admin-resultados');
?>
    <h1 class="text-4xl">Cargar resultados</h1>
    <?php if ($msg): ?><div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-300 text-sm rounded-xl px-4 py-3"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="bg-red-500/10 border border-red-500/40 text-red-300 text-sm rounded-xl px-4 py-3"><?= e($err) ?></div><?php endif; ?>

    <?php if ($jugados): ?>
        <section class="space-y-3">
            <h2 class="text-xl">Partidos de Peritas sin resultado</h2>
            <?php foreach ($jugados as $m): ?>
                <form method="POST" class="rounded-2xl border border-[#8052ff]/40 bg-[#0a0a0a] p-4 flex flex-wrap items-center gap-3 text-sm">
                    <input type="hidden" name="torneo" value="<?= e($m['tournament']) ?>">
                    <input type="hidden" name="fecha" value="<?= e(substr($m['match_date'], 0, 10)) ?>">
                    <input type="hidden" name="local" value="<?= e($m['home_team']) ?>">
                    <input type="hidden" name="visitante" value="<?= e($m['away_team']) ?>">
                    <span class="flex-grow"><?= e($m['home_team']) ?> vs <?= e($m['away_team']) ?> <span class="text-slate-400">· <?= date('d/m/Y', strtotime($m['match_date'])) ?></span></span>
                    <input type="number" name="goles_local" min="0" required class="<?= $campo ?> !w-16 text-center" aria-label="Goles de <?= e($m['home_team']) ?>">
                    <span>-</span>
                    <input type="number" name="goles_visitante" min="0" required class="<?= $campo ?> !w-16 text-center" aria-label="Goles de <?= e($m['away_team']) ?>">
                    <button class="px-4 py-2 rounded-full bg-gradient-to-r from-[#ffb829] to-yellow-500 text-black font-bold text-xs">Guardar</button>
                </form>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <section class="space-y-3">
        <h2 class="text-xl">Otro resultado del torneo</h2>
        <form method="POST" class="rounded-2xl border border-[#2a2a2a]/60 bg-[#0a0a0a] p-5 grid sm:grid-cols-6 gap-3">
            <input name="torneo" value="LIGA DE PRIMERA DIVISIÓN" required class="<?= $campo ?> sm:col-span-4" aria-label="Torneo">
            <input type="date" name="fecha" required class="<?= $campo ?> sm:col-span-2" aria-label="Fecha">
            <input name="local" placeholder="Equipo local" required class="<?= $campo ?> sm:col-span-2">
            <input type="number" name="goles_local" min="0" placeholder="Goles" required class="<?= $campo ?>">
            <input type="number" name="goles_visitante" min="0" placeholder="Goles" required class="<?= $campo ?>">
            <input name="visitante" placeholder="Equipo visitante" required class="<?= $campo ?> sm:col-span-2">
            <button class="sm:col-span-6 py-2.5 rounded-full bg-gradient-to-r from-[#ffb829] to-yellow-500 text-black font-bold text-sm">Cargar resultado</button>
        </form>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl">Resultados cargados</h2>
        <div class="rounded-2xl border border-[#1f1f1f] divide-y divide-[#141414]">
            <?php foreach ($resultados as $r): ?>
                <form method="POST" class="flex items-center justify-between gap-3 px-5 py-3 text-sm" onsubmit="return confirm('¿Eliminar este resultado?')">
                    <input type="hidden" name="accion" value="borrar"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <span><span class="text-slate-400"><?= date('d/m/Y', strtotime($r['fecha'])) ?> ·</span> <?= e($r['local']) ?> <b class="tabular-nums"><?= (int)$r['goles_local'] ?> - <?= (int)$r['goles_visitante'] ?></b> <?= e($r['visitante']) ?></span>
                    <button class="text-xs text-red-300 hover:underline">Borrar</button>
                </form>
            <?php endforeach; ?>
            <?php if (!$resultados): ?><p class="px-5 py-4 text-sm text-slate-400">Todavía no hay resultados.</p><?php endif; ?>
        </div>
    </section>
<?php club_fin();