<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Peritas FC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="flex flex-col min-h-screen bg-[#0B132B] text-slate-100 font-['Poppins'] justify-center items-center p-4">

    <div class="max-w-md w-full bg-[#1C2541] border border-[#3A506B]/50 rounded-2xl p-8 space-y-6 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-[#D4AF37] to-yellow-500"></div>
        
        <div class="text-center space-y-2">
            <h1 class="text-2xl font-extrabold text-white">Asociate a Peritas FC</h1>
            <p class="text-xs text-slate-400">Completá el formulario digital y obtené tu carnet inteligente</p>
        </div>

        <!-- Formulario que conecta con tu controlador PHP -->
        <form action="register.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-300 mb-1">Nombre Completo</label>
                <input type="text" name="name" required placeholder="Ej: Juan Pérez" class="w-full bg-slate-900 border border-[#3A506B] px-4 py-2.5 rounded-xl text-slate-100 focus:outline-none focus:border-[#D4AF37] transition text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-300 mb-1">DNI (Documento)</label>
                <input type="text" name="dni" required placeholder="Ej: 45123987" class="w-full bg-slate-900 border border-[#3A506B] px-4 py-2.5 rounded-xl text-slate-100 focus:outline-none focus:border-[#D4AF37] transition text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-300 mb-1">Correo Electrónico</label>
                <input type="email" name="email" required placeholder="socio@peritias.com" class="w-full bg-slate-900 border border-[#3A506B] px-4 py-2.5 rounded-xl text-slate-100 focus:outline-none focus:border-[#D4AF37] transition text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-300 mb-1">Contraseña</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full bg-slate-900 border border-[#3A506B] px-4 py-2.5 rounded-xl text-slate-100 focus:outline-none focus:border-[#D4AF37] transition text-sm">
            </div>

            <button type="submit" class="w-full py-3 bg-gradient-to-r from-[#D4AF37] to-yellow-500 text-[#0B132B] font-black tracking-wide rounded-xl shadow-lg hover:scale-[1.01] transition">
                CREAR MI CUENTA DE SOCIO
            </button>
        </form>

        <div class="text-center border-t border-[#3A506B]/30 pt-4">
            <p class="text-xs text-slate-400">¿Ya sos socio registrado? <a href="login.php" class="text-[#D4AF37] hover:underline font-bold">Iniciá sesión</a></p>
        </div>
    </div>

</body>
</html>