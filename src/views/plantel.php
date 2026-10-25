<?php
// Plantel del primer equipo: se lee de assets/data/plantel.json
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/_layouts/club.php';

$archivo = __DIR__ . '/../../assets/data/plantel.json';
$jugadores = is_readable($archivo) ? json_decode(file_get_contents($archivo), true) : null;
if (!is_array($jugadores)) {
    if (is_readable($archivo)) error_log('plantel.json tiene un error de formato');
    $jugadores = [];
}

// Agrupados por posición, en el orden de la cancha, y por número de camiseta
$orden = ['Arquero' => 'Arqueros', 'Defensor' => 'Defensores', 'Mediocampista' => 'Mediocampistas', 'Delantero' => 'Delanteros'];
$grupos = [];
foreach ($jugadores as $j) {
    if (empty($j['nombre'])) continue;
    $grupos[$j['posicion'] ?? 'Otros'][] = $j;
}
foreach ($grupos as &$g) usort($g, fn($a, $b) => ($a['numero'] ?? 99) <=> ($b['numero'] ?? 99));
unset($g);
$titulos = $orden + array_combine(array_keys($grupos), array_keys($grupos));   // posiciones que no estén en $orden van al final

club_inicio($pdo, 'Plantel', 'plantel');
?>
    <section class="space-y-2">
        <p class="text-xs font-semibold uppercase tracking-wide text-[#ffb829]">Primer equipo · Temporada <?= date('Y') ?></p>
        <h1 class="text-5xl sm:text-6xl">Plantel</h1>
    </section>

    <?php if (!$grupos): ?><p class="text-slate-400 text-sm">Todavía no hay jugadores cargados.</p><?php endif; ?>

    <?php foreach ($titulos as $posicion => $titulo): if (empty($grupos[$posicion])) continue; ?>
        <section class="space-y-4">
            <h2 class="text-2xl"><?= e($titulo) ?></h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php foreach ($grupos[$posicion] as $j): ?>
                    <article class="rounded-3xl overflow-hidden border border-[#1f1f1f] bg-[#0a0a0a]">
                        <div class="relative aspect-[4/5] bg-gradient-to-b from-[#141414] to-black flex items-center justify-center">
                            <?php if (!empty($j['foto'])): ?>
                                <img src="<?= e($j['foto']) ?>" alt="<?= e($j['nombre']) ?>" class="absolute inset-0 w-full h-full object-cover object-top">
                            <?php else: ?>
                                <img src="/assets/img/escudo-chico.png" alt="" class="w-1/2 opacity-20">
                            <?php endif; ?>
                            <span class="absolute top-3 left-4 text-4xl font-semibold text-white drop-shadow tabular-nums"><?= (int)($j['numero'] ?? 0) ?></span>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[11px] uppercase tracking-wide text-[#8052ff] font-semibold"><?= e($j['posicion'] ?? '') ?></p>
                            <p class="text-base text-white"><?= e($j['nombre']) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
<?php club_fin();