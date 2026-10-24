<?php
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$avisos = ['creada' => 'Noticia publicada.', 'editada' => 'Cambios guardados.', 'borrada' => 'Noticia eliminada.'];
$aviso = $avisos[$_GET['ok'] ?? ''] ?? '';
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Noticias · Administración - Peritas FC</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">    <link rel="stylesheet" href="/assets/css/dala.css">
    <link rel="icon" type="image/png" href="/assets/img/escudo-chico.png">
</head>
<body class="bg-[#000000] text-slate-100 font-['Poppins'] min-h-screen">
<header class="bg-[#0a0a0a] border-b border-[#2a2a2a]/30"><div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
    <span class="font-extrabold text-lg"><img src="/assets/img/escudo-chico.png" alt="" class="inline-block h-9 w-auto mr-2 align-middle">PERITAS <span class="text-[#ffb829]">FC</span> · Administración</span>
    <a href="/src/views/index.php" class="text-sm text-slate-300 hover:text-white">Volver a mi portal</a></div></header>
<main class="max-w-4xl mx-auto px-4 py-8 space-y-6">
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-3xl font-black">Noticias</h1>
        <a href="/src/controllers/posts/form.php" class="px-5 py-2.5 rounded-full bg-gradient-to-r from-[#ffb829] to-yellow-500 text-[#000000] font-bold text-sm">+ Nueva noticia</a>
    </div>
    <p class="text-sm text-slate-400">Lo que publiques acá aparece en la sección Noticias de la página principal.</p>
    <?php if ($aviso): ?><div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-300 text-sm rounded-xl px-4 py-3"><?= e($aviso) ?></div><?php endif; ?>

    <div class="rounded-2xl border border-[#2a2a2a]/40 divide-y divide-[#2a2a2a]/30">
        <?php foreach ($posts as $post): ?>
            <div class="flex items-center gap-4 px-5 py-4">
                <img src="<?= e($post['image_url'] ?: IMAGEN_NOTICIA_DEFAULT) ?>" alt="" class="w-20 h-12 rounded-lg object-cover bg-slate-900 shrink-0">
                <div class="flex-grow min-w-0">
                    <p class="font-bold truncate"><?= e($post['title']) ?></p>
                    <p class="text-xs text-slate-400"><?= e(CATEGORIAS_NOTICIAS[strtolower($post['category'])] ?? $post['category']) ?> · <?= e(fecha_relativa($post['created_at'])) ?> · <?= e($post['user_name'] ?? 'Peritas FC') ?></p>
                </div>
                <a href="/src/controllers/posts/form.php?id=<?= e($post['id']) ?>" class="px-3 py-1.5 rounded-full border border-[#2a2a2a] text-xs font-bold hover:border-[#ffb829]">Editar</a>
                <form method="POST" action="/src/controllers/posts/delete.php" onsubmit="return confirm('¿Eliminar esta noticia?')">
                    <input type="hidden" name="id" value="<?= e($post['id']) ?>">
                    <button class="px-3 py-1.5 rounded-full border border-red-500/50 text-red-300 text-xs font-bold hover:bg-red-500/10">Borrar</button>
                </form>
            </div>
        <?php endforeach; ?>
        <?php if (!$posts): ?><p class="px-5 py-4 text-sm text-slate-400">Todavía no hay noticias.</p><?php endif; ?>
    </div>
</main></body></html>