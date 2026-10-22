<?php
// src/views/pagos/resultado.php
$estado = $_GET['estado'] ?? 'pendiente';
$mensajes = [
    'aprobado'  => ['¡Pago aprobado!', 'Tu pago se acreditó correctamente. ¡Gracias por apoyar a Peritas FC!', 'text-emerald-400', 'fa-circle-check'],
    'rechazado' => ['Pago rechazado', 'Mercado Pago no pudo procesar el pago. Podés intentar de nuevo.', 'text-red-400', 'fa-circle-xmark'],
    'pendiente' => ['Pago pendiente', 'Estamos esperando la confirmación de Mercado Pago.', 'text-yellow-400', 'fa-clock'],
];
[$titulo, $texto, $color, $icono] = $mensajes[$estado] ?? $mensajes['pendiente'];
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?> - Peritas FC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="h-full flex items-center justify-center p-6">
    <div class="max-w-md text-center bg-[#1C2541] border border-[#3A506B]/50 rounded-2xl p-8 space-y-4">
        <i class="fa-solid <?= $icono ?> text-5xl <?= $color ?>"></i>
        <h1 class="text-2xl font-extrabold"><?= htmlspecialchars($titulo) ?></h1>
        <p class="text-sm text-slate-300"><?= htmlspecialchars($texto) ?></p>
        <a href="/index.php" class="inline-block px-6 py-2 rounded-full bg-[#D4AF37] text-[#0B132B] font-bold">Volver al inicio</a>
    </div>
</body>
</html>
