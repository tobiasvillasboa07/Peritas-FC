<?php
require_once __DIR__ . '/../config/bootstrap.php';
if (empty($_SESSION['user_id'])) { header('Location: /src/views/auth/login.php'); exit; }
$uid = $_SESSION['user_id'];

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function plata($n) { return $n == 0 ? 'Gratis' : 'AR$ ' . number_format((float)$n, 0, ',', '.'); }

// sector => [nombre, precio, es_popular]
const SECTORES = [
    'popular_norte' => ['Tribuna Popular Norte', 8000, true],
    'popular_sur'   => ['Tribuna Popular Sur', 8000, true],
    'platea_alta'   => ['Platea Alta Lateral', 15000, false],
    'platea_baja'   => ['Platea Baja Preferencial', 22500, false],
];

$st = $pdo->prepare('SELECT categoria FROM users WHERE id = ?');
$st->execute([$uid]);
$esPleno = str_starts_with((string)$st->fetchColumn(), 'Pleno');   // Pleno: Tribuna Popular gratis
$precio = fn($k) => ($esPleno && SECTORES[$k][2]) ? 0 : SECTORES[$k][1];

$msg = $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $k = $_POST['sector'] ?? '';
    $mid = $_POST['match_id'] ?? '';
    $ok = $pdo->prepare('SELECT 1 FROM matches WHERE id = ? AND match_date >= ?');
    $ok->execute([$mid, date('Y-m-d H:i:s')]);
    if (!isset(SECTORES[$k]) || !$ok->fetchColumn()) {
        $err = 'Elegí un partido y un sector válidos.';
    } else {
        try {
            $pdo->prepare('INSERT INTO tickets (user_id, match_id, sector, price) VALUES (?, ?, ?, ?)')
                ->execute([$uid, $mid, SECTORES[$k][0], $precio($k)]);
            $msg = '¡Listo! Tu entrada quedó guardada en "Mis entradas".';
        } catch (PDOException $ex) {
            $err = str_contains($ex->getMessage(), 'No quedan') ? 'No quedan entradas para ese partido.'
                 : ($ex->getCode() === '23000' ? 'Ya tenés una entrada para ese partido y sector.' : 'No se pudo sacar la entrada.');
        }
    }
}

$q = $pdo->prepare('SELECT m.*, (SELECT COUNT(*) FROM tickets t WHERE t.match_id = m.id) AS vendidas FROM matches m WHERE match_date >= ? ORDER BY match_date');
$q->execute([date('Y-m-d H:i:s')]);
$partidos = $q->fetchAll();
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Entradas - Peritas FC</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
<style>:focus-visible{outline:2px solid #D4AF37;outline-offset:3px}</style></head>
<body class="bg-[#0B132B] text-slate-100 font-['Poppins'] min-h-screen">
<header class="bg-[#1C2541] border-b border-[#3A506B]/30"><div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
    <a href="/" class="font-extrabold text-lg">PERITAS <span class="text-[#D4AF37]">FC</span></a>
    <a href="/src/views/index.php" class="text-sm text-slate-300 hover:text-white">Volver a mi portal</a></div></header>
<main class="max-w-4xl mx-auto px-4 py-8 space-y-6">
    <h1 class="text-3xl font-black">Sacá tu entrada</h1>
    <?php if ($esPleno): ?><p class="text-sm text-[#D4AF37]">Como socio Pleno, la Tribuna Popular es gratis.</p><?php endif; ?>
    <?php if ($msg): ?><div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-300 text-sm rounded-xl px-4 py-3"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="bg-red-500/10 border border-red-500/40 text-red-300 text-sm rounded-xl px-4 py-3"><?= e($err) ?></div><?php endif; ?>
    <?php foreach ($partidos as $m): $quedan = $m['capacity'] - $m['vendidas']; ?>
        <form method="POST" class="rounded-2xl border border-[#3A506B]/40 bg-[#1C2541]/60 p-5 flex flex-col sm:flex-row sm:items-center gap-4 justify-between">
            <input type="hidden" name="match_id" value="<?= e($m['id']) ?>">
            <div><p class="font-bold text-lg">PERITAS FC vs <?= e($m['away_team']) ?></p>
                <p class="text-sm text-slate-400"><?= e($m['tournament']) ?> · <?= date('d/m/Y H:i', strtotime($m['match_date'])) ?> hs · <?= e($m['stadium']) ?></p>
                <p class="text-xs mt-1 <?= $quedan > 0 ? 'text-slate-400' : 'text-red-400' ?>"><?= $quedan > 0 ? "Quedan $quedan entradas" : 'Agotado' ?></p></div>
            <div class="flex gap-2">
                <select name="sector" class="bg-slate-900 border border-[#3A506B] rounded-xl px-3 py-2 text-sm">
                    <?php foreach (SECTORES as $k => $s): ?><option value="<?= e($k) ?>"><?= e($s[0]) ?> - <?= plata($precio($k)) ?></option><?php endforeach; ?>
                </select>
                <button <?= $quedan > 0 ? '' : 'disabled' ?> class="px-5 py-2 rounded-full bg-gradient-to-r from-[#D4AF37] to-yellow-500 text-[#0B132B] font-bold text-sm disabled:opacity-40">Sacar entrada</button>
            </div>
        </form>
    <?php endforeach; ?>
    <?php if (!$partidos): ?><p class="text-slate-400">No hay partidos próximos cargados todavía.</p><?php endif; ?>
</main></body></html>
