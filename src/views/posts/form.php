<?php
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$campo = 'w-full bg-slate-900 border border-[#2a2a2a] rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-[#ffb829]';
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $post ? 'Editar' : 'Nueva' ?> noticia · Administración - Peritas FC</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">    <link rel="stylesheet" href="/assets/css/dala.css">
    <link rel="icon" type="image/png" href="/assets/img/escudo-chico.png">
</head>
<body class="bg-[#000000] text-slate-100 font-['Poppins'] min-h-screen">
<header class="bg-[#0a0a0a] border-b border-[#2a2a2a]/30"><div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
    <span class="font-extrabold text-lg"><img src="/assets/img/escudo-chico.png" alt="" class="inline-block h-9 w-auto mr-2 align-middle">PERITAS <span class="text-[#ffb829]">FC</span> · Administración</span>
    <a href="/src/controllers/posts/index.php" class="text-sm text-slate-300 hover:text-white">Volver a noticias</a></div></header>
<main class="max-w-2xl mx-auto px-4 py-8 space-y-6">
    <h1 class="text-3xl font-black"><?= $post ? 'Editar noticia' : 'Nueva noticia' ?></h1>
    <?php if (isset($_GET['error'])): ?><div class="bg-red-500/10 border border-red-500/40 text-red-300 text-sm rounded-xl px-4 py-3">Completá el título y el texto.</div><?php endif; ?>

    <form method="POST" action="/src/controllers/posts/<?= $post ? 'update' : 'store' ?>.php" class="rounded-2xl border border-[#2a2a2a]/40 bg-[#0a0a0a]/60 p-6 space-y-4">
        <?php if ($post): ?><input type="hidden" name="id" value="<?= e($post['id']) ?>"><?php endif; ?>
        <div>
            <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Título</label>
            <input name="title" required value="<?= e($post['title'] ?? '') ?>" class="<?= $campo ?>">
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Texto (resumen que se ve en la tarjeta)</label>
            <textarea name="content" required rows="4" class="<?= $campo ?>"><?= e($post['content'] ?? '') ?></textarea>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Categoría</label>
            <select name="category" class="<?= $campo ?>">
                <?php foreach (CATEGORIAS_NOTICIAS as $k => $nombre): ?>
                    <option value="<?= $k ?>" <?= strtolower($post['category'] ?? '') === $k ? 'selected' : '' ?>><?= $nombre ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Imagen</label>
            <select name="image_url" class="<?= $campo ?>" onchange="document.getElementById('vista').src = this.value || '<?= IMAGEN_NOTICIA_DEFAULT ?>'">
                <option value="">Sin imagen (logo del club)</option>
                <?php foreach ($imagenes as $img): ?>
                    <option value="<?= e($img) ?>" <?= ($post['image_url'] ?? '') === $img ? 'selected' : '' ?>><?= e(basename($img)) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="text-xs text-slate-400 mt-1">Para sumar una foto nueva, subila a la carpeta assets/img del proyecto.</p>
            <img id="vista" src="<?= e(($post['image_url'] ?? '') ?: IMAGEN_NOTICIA_DEFAULT) ?>" alt="" class="mt-3 w-full h-48 object-cover rounded-xl bg-slate-900">
        </div>
        <button class="w-full py-2.5 rounded-full bg-gradient-to-r from-[#ffb829] to-yellow-500 text-[#000000] font-bold text-sm"><?= $post ? 'Guardar cambios' : 'Publicar noticia' ?></button>
    </form>
</main></body></html>