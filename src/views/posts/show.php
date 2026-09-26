<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Publicación - Peritas FC</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-8 max-w-3xl mx-auto space-y-6">
    <a href="index.php" class="text-xs text-amber-400 hover:underline flex items-center gap-1">← Volver al listado</a>

    <div class="bg-slate-900 border border-slate-800 p-8 rounded-3xl shadow-2xl space-y-4">
        <h1 class="text-3xl font-black text-white"><?= htmlspecialchars($post['title']) ?></h1>
        
        <div class="flex items-center gap-4 text-xs text-slate-400 border-b border-slate-800 pb-4">
            <span><strong>Autor:</strong> <?= htmlspecialchars($post['user_name']) ?></span>
            <span><strong>Creado:</strong> <?= htmlspecialchars($post['created_at']) ?></span>
            <?php if (!empty($post['updated_at'])): ?>
                <span><strong>Actualizado:</strong> <?= htmlspecialchars($post['updated_at']) ?></span>
            <?php endif; ?>
        </div>

        <div class="text-slate-200 text-sm leading-relaxed whitespace-pre-line pt-2">
            <?= htmlspecialchars($post['content']) ?>
        </div>
    </div>
</body>
</html>