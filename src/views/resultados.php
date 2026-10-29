<?php
// Resultados y tabla de posiciones (página pública)
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/resultados.php';
require_once __DIR__ . '/../models/partidos.php';   // foto_partido()
require_once __DIR__ . '/_layouts/club.php';

preparar_resultados($pdo);
$ultimos = ultimos_partidos_del_club($pdo, 12);   // solo los de Peritas FC (la tabla usa todos)
$torneos = torneos_con_resultados($pdo);

club_inicio($pdo, 'Resultados', 'resultados');
?>
    <section class="space-y-2">
        <p class="text-xs font-semibold uppercase tracking-wide text-[#ffb829]">Temporada <?= date('Y') ?></p>
        <h1 class="text-5xl sm:text-6xl">Resultados</h1>
    </section>

    <section class="space-y-4">
        <h2 class="text-2xl">Últimos partidos de Peritas FC</h2>
        <?php if (!$ultimos): ?><p class="text-slate-400 text-sm">Todavía no hay resultados cargados.</p><?php endif; ?>
        <div class="grid sm:grid-cols-2 gap-3">
            <?php foreach ($ultimos as $r):
                $rival = $r['local'] === NOMBRE_CLUB ? $r['visitante'] : $r['local'];
                $fondo = foto_partido($rival);   // foto del rival (assets/img/partidos) ?>
                <article class="rounded-2xl border border-[#8052ff]/60 bg-[#0a0a0a] px-5 py-5"<?php if ($fondo): ?> style="background: linear-gradient(rgba(0,0,0,.72), rgba(0,0,0,.72)), url('<?= e($fondo) ?>') center / cover"<?php endif; ?>>
                    <p class="text-[11px] text-slate-400 uppercase tracking-wide"><?= e($r['torneo']) ?> · <?= date('d/m/Y', strtotime($r['fecha'])) ?></p>
                    <div class="mt-2 grid grid-cols-[1fr_auto_1fr] items-center gap-3">
                        <span class="text-sm <?= $r['local'] === NOMBRE_CLUB ? 'text-white font-semibold' : 'text-slate-300' ?>"><?= e($r['local']) ?></span>
                        <span class="text-2xl font-semibold tabular-nums"><?= (int)$r['goles_local'] ?> - <?= (int)$r['goles_visitante'] ?></span>
                        <span class="text-sm text-right <?= $r['visitante'] === NOMBRE_CLUB ? 'text-white font-semibold' : 'text-slate-300' ?>"><?= e($r['visitante']) ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <?php foreach ($torneos as $torneo): $tabla = tabla_posiciones($pdo, $torneo); ?>
        <section class="space-y-4">
            <h2 class="text-2xl">Tabla de posiciones <span class="text-slate-400 text-base">· <?= e($torneo) ?></span></h2>
            <div class="overflow-x-auto rounded-2xl border border-[#1f1f1f]">
                <table class="w-full text-sm tabular-nums">
                    <thead class="text-[11px] uppercase tracking-wide text-slate-400">
                        <tr class="border-b border-[#1f1f1f]">
                            <th class="px-4 py-3 text-left">#</th>
                            <th class="px-4 py-3 text-left">Equipo</th>
                            <th class="px-3 py-3 text-right" title="Partidos jugados">PJ</th>
                            <th class="px-3 py-3 text-right" title="Ganados">G</th>
                            <th class="px-3 py-3 text-right" title="Empatados">E</th>
                            <th class="px-3 py-3 text-right" title="Perdidos">P</th>
                            <th class="px-3 py-3 text-right" title="Goles a favor">GF</th>
                            <th class="px-3 py-3 text-right" title="Goles en contra">GC</th>
                            <th class="px-3 py-3 text-right" title="Diferencia de gol">DG</th>
                            <th class="px-4 py-3 text-right">Pts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tabla as $i => $t): $esNuestro = $t['equipo'] === NOMBRE_CLUB; ?>
                            <tr class="border-b border-[#141414] last:border-0 <?= $esNuestro ? 'bg-[#8052ff]/10' : '' ?>">
                                <td class="px-4 py-3 text-slate-400"><?= $i + 1 ?></td>
                                <td class="px-4 py-3 <?= $esNuestro ? 'text-white font-semibold' : '' ?>"><?= e($t['equipo']) ?></td>
                                <td class="px-3 py-3 text-right"><?= $t['pj'] ?></td>
                                <td class="px-3 py-3 text-right"><?= $t['pg'] ?></td>
                                <td class="px-3 py-3 text-right"><?= $t['pe'] ?></td>
                                <td class="px-3 py-3 text-right"><?= $t['pp'] ?></td>
                                <td class="px-3 py-3 text-right"><?= $t['gf'] ?></td>
                                <td class="px-3 py-3 text-right"><?= $t['gc'] ?></td>
                                <td class="px-3 py-3 text-right"><?= $t['dg'] > 0 ? '+' : '' ?><?= $t['dg'] ?></td>
                                <td class="px-4 py-3 text-right font-semibold text-white"><?= $t['pts'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-slate-500">3 puntos por partido ganado y 1 por empate. Si hay empate en puntos, desempata la diferencia de gol y después los goles a favor.</p>
        </section>
    <?php endforeach; ?>
<?php club_fin();