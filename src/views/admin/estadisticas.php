<?php
// Administración: estadísticas del club (solo admin)
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/admin.php';
require_once __DIR__ . '/../../models/mercadopago.php';   // tablas de pagos y generar_cuota_mes()
require_once __DIR__ . '/../_layouts/club.php';
requerir_admin($pdo);

function plata($n) { return 'AR$ ' . number_format((float)$n, 0, ',', '.'); }

// Nos aseguramos de que todos tengan generada la cuota del mes, así la deuda está al día
foreach ($pdo->query('SELECT id FROM users')->fetchAll(PDO::FETCH_COLUMN) as $id) generar_cuota_mes($pdo, $id);

$socios        = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$conDeuda      = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM cuotas WHERE estado = 'pendiente'")->fetchColumn();
$recEntradas   = (float)$pdo->query("SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE estado = 'aprobado'")->fetchColumn();
$recCuotas     = (float)$pdo->query("SELECT COALESCE(SUM(monto), 0) FROM cuotas WHERE estado = 'pagada'")->fetchColumn();
$entradas      = (int)$pdo->query('SELECT COUNT(*) FROM tickets')->fetchColumn();
$entradasGratis = (int)$pdo->query('SELECT COUNT(*) FROM tickets WHERE price = 0')->fetchColumn();

// Entradas por partido: primero los próximos (del más cercano al más lejano) y después los ya jugados
$porPartido = $pdo->query("SELECT m.away_team, m.match_date, m.capacity,
        COUNT(t.id) AS vendidas, COALESCE(SUM(t.price), 0) AS recaudado
    FROM matches m LEFT JOIN tickets t ON t.match_id = m.id
    GROUP BY m.id")->fetchAll();
$ahora = date('Y-m-d H:i:s');
usort($porPartido, fn($a, $b) => [$a['match_date'] < $ahora, $a['match_date'] < $ahora ? -strtotime($a['match_date']) : strtotime($a['match_date'])]
                             <=> [$b['match_date'] < $ahora, $b['match_date'] < $ahora ? -strtotime($b['match_date']) : strtotime($b['match_date'])]);

// Socios que deben cuotas
$deudores = $pdo->query("SELECT u.name, u.email, COUNT(c.id) AS cuotas, SUM(c.monto) AS total
    FROM cuotas c JOIN users u ON u.id = c.user_id
    WHERE c.estado = 'pendiente'
    GROUP BY u.id ORDER BY total DESC, u.name")->fetchAll();

$tarjetas = [
    ['Socios registrados', number_format($socios, 0, ',', '.'), $socios - $conDeuda . ' al día · ' . $conDeuda . ' con deuda'],
    ['Recaudado en entradas', plata($recEntradas), 'pagos aprobados por Mercado Pago'],
    ['Recaudado en cuotas', plata($recCuotas), 'cuotas sociales pagadas'],
    ['Entradas emitidas', number_format($entradas, 0, ',', '.'), $entradasGratis . ' gratis para socios'],
];

club_inicio($pdo, 'Estadísticas · Administración', 'estadisticas');
?>
    <section class="space-y-2">
        <p class="text-xs font-semibold uppercase tracking-wide text-[#ffb829]">Administración</p>
        <h1 class="text-5xl">Estadísticas del club</h1>
    </section>

    <!-- Totales -->
    <section class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php foreach ($tarjetas as [$titulo, $valor, $detalle]): ?>
            <div class="rounded-3xl border border-[#1f1f1f] bg-[#0a0a0a] p-6 space-y-2">
                <p class="text-xs uppercase tracking-wide text-slate-400"><?= e($titulo) ?></p>
                <p class="text-4xl text-white tabular-nums"><?= e($valor) ?></p>
                <p class="text-xs text-slate-400"><?= e($detalle) ?></p>
            </div>
        <?php endforeach; ?>
    </section>

    <!-- Entradas por partido -->
    <section class="space-y-4">
        <h2 class="text-2xl">Entradas por partido</h2>
        <div class="overflow-x-auto rounded-2xl border border-[#1f1f1f]">
            <table class="w-full text-sm tabular-nums">
                <thead class="text-[11px] uppercase tracking-wide text-slate-400">
                    <tr class="border-b border-[#1f1f1f]">
                        <th class="px-4 py-3 text-left">Partido</th>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3 text-left w-1/3">Ocupación</th>
                        <th class="px-4 py-3 text-right">Vendidas</th>
                        <th class="px-4 py-3 text-right">Recaudado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($porPartido as $p):
                        $porcentaje = $p['capacity'] > 0 ? round($p['vendidas'] * 100 / $p['capacity']) : 0;
                        $textoPorcentaje = ($p['vendidas'] > 0 && $porcentaje < 1) ? '<1%' : $porcentaje . '%';
                        $jugado = strtotime($p['match_date']) < time(); ?>
                        <tr class="border-b border-[#141414] last:border-0">
                            <td class="px-4 py-3 <?= $jugado ? 'text-slate-400' : 'text-white' ?>">vs <?= e($p['away_team']) ?></td>
                            <td class="px-4 py-3 text-slate-400"><?= date('d/m/Y', strtotime($p['match_date'])) ?><?= $jugado ? ' · jugado' : '' ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3" title="<?= (int)$p['vendidas'] ?> de <?= (int)$p['capacity'] ?> entradas (<?= e($textoPorcentaje) ?>)">
                                    <div class="flex-grow h-2 rounded-full bg-[#1a1a1a]" role="img" aria-label="Ocupación <?= e($textoPorcentaje) ?>">
                                        <div class="h-2 rounded-full bg-[#8052ff]" style="width: <?= max($porcentaje, $p['vendidas'] > 0 ? 2 : 0) ?>%"></div>
                                    </div>
                                    <span class="w-10 text-right text-slate-300"><?= e($textoPorcentaje) ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right"><?= (int)$p['vendidas'] ?> / <?= (int)$p['capacity'] ?></td>
                            <td class="px-4 py-3 text-right"><?= plata($p['recaudado']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$porPartido): ?><tr><td colspan="5" class="px-4 py-4 text-slate-400">No hay partidos cargados.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Deudores -->
    <section class="space-y-4">
        <h2 class="text-2xl">Socios con cuotas sin pagar <span class="text-slate-400 text-base">· <?= count($deudores) ?></span></h2>
        <div class="overflow-x-auto rounded-2xl border border-[#1f1f1f]">
            <table class="w-full text-sm tabular-nums">
                <thead class="text-[11px] uppercase tracking-wide text-slate-400">
                    <tr class="border-b border-[#1f1f1f]">
                        <th class="px-4 py-3 text-left">Socio</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-right">Cuotas</th>
                        <th class="px-4 py-3 text-right">Debe</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deudores as $d): ?>
                        <tr class="border-b border-[#141414] last:border-0">
                            <td class="px-4 py-3 text-white"><?= e($d['name']) ?></td>
                            <td class="px-4 py-3 text-slate-400"><?= e($d['email']) ?></td>
                            <td class="px-4 py-3 text-right"><?= (int)$d['cuotas'] ?></td>
                            <td class="px-4 py-3 text-right text-[#ffb829]"><?= plata($d['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$deudores): ?><tr><td colspan="4" class="px-4 py-4 text-slate-400">Todos los socios están al día.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php club_fin();