<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Novedades - Peritas FC</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-8 max-w-4xl mx-auto space-y-6">
    <h1 class="text-3xl font-black text-amber-400">Listado de Publicaciones (Posts)</h1>

    <?php if (empty($posts)): ?>
        <p class="text-slate-400">No hay publicaciones registradas en la base de datos.</p>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($posts as $post): ?>
                <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl shadow-lg space-y-2">
                    <h2 class="text-xl font-bold text-white"><?= htmlspecialchars($post['title']) ?></h2>
                    <p class="text-xs text-amber-500">Por: <?= htmlspecialchars($post['user_name']) ?> | Fecha: <?= htmlspecialchars($post['created_at']) ?></p>
                    <p class="text-sm text-slate-300 line-clamp-2"><?= htmlspecialchars($post['content']) ?></p>
                    <div class="pt-2">
                        <a href="show.php?id=<?= $post['id'] ?>" class="px-4 py-2 bg-amber-500 text-slate-950 font-bold text-xs rounded-xl hover:bg-amber-400 transition">
                            Ver Detalle Completo
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>
</html>