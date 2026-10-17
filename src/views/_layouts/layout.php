<?php
require_once __DIR__ . '/../../config/bootstrap.php';

# Si no estoy logueado, redirige al login
if (!isset($_SESSION['user'])) {
    header('Location: /src/views/auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peritas FC - Portal Oficial de Socios</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        club: {
                            dark: '#0B132B',      // Azul marino ultra oscuro
                            navy: '#1C2541',      // Azul marino principal
                            blue: '#3A506B',      // Azul intermedio
                            gold: '#D4AF37',      // Dorado oficial
                            goldDark: '#AA800E',  // Dorado oscuro para contrastes
                            accent: '#00F2FE',    // Cyan dinámico
                        }
                    },
                    fontFamily: {
                        sans: ['Poppins', 'Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        html { scroll-behavior: smooth; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #0B132B; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #D4AF37; border-radius: 3px; }
        .active-tab { color: #D4AF37 !important; border-bottom: 2px solid #D4AF37; }
    </style>
</head>
<body class="flex flex-col min-h-screen bg-club-dark text-slate-100 custom-scrollbar font-sans pb-16 md:pb-0">

    <!-- HEADER / NAVIGATION -->
   <header class="sticky top-0 z-50 bg-club-navy/95 backdrop-blur-md border-b border-club-blue/30 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
        <a href="/" class="flex items-center gap-3 group">
            <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-club-gold via-yellow-500 to-club-navy p-0.5 shadow-md flex items-center justify-center">
                <div class="w-full h-full bg-club-navy rounded-full flex items-center justify-center font-extrabold text-club-gold text-lg tracking-wider">
                    PFC
                </div>
            </div>
            <div>
                <span class="text-xl font-black tracking-tight text-white group-hover:text-club-gold transition">PERITAS <span class="text-club-gold">FC</span></span>
                <p class="text-[10px] text-slate-400 tracking-widest uppercase">El club de la tecnología</p>
            </div>
        </a>

        <nav class="hidden md:flex items-center gap-8 font-semibold text-sm tracking-wide text-slate-300">
            <a href="/" class="hover:text-club-gold transition py-2">INICIO</a>
            <a href="/src/views/posts/index.php" class="hover:text-club-gold transition py-2">NOTICIAS</a>
            <a href="/src/views/entradas.php" class="hover:text-club-gold transition py-2">ENTRADAS</a>
            <a href="/src/views/index.php" class="hover:text-club-gold transition py-2">PORTAL SOCIO</a>
            <button type="button" id="btn-asistente"
                class="flex items-center gap-2 px-4 py-2 rounded-full border border-club-gold/50 text-club-gold hover:bg-club-gold hover:text-club-navy transition">
                ASISTENTE
            </button>
        </nav>

        <div class="hidden md:flex items-center gap-4">
            <div class="text-right">
                <p class="text-sm font-semibold text-white"><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Socio'); ?></p>
                <p class="text-xs text-club-gold"><?= htmlspecialchars($_SESSION['user']['categoria'] ?? 'Socio'); ?></p>
            </div>
            <a href="/src/controllers/auth/logout.php"
               class="px-4 py-2 rounded-full border border-slate-500 text-sm font-semibold text-white hover:border-red-400 hover:text-red-400 transition">
                Salir
            </a>
        </div>
    </div>
</header>
    <!-- MAIN CONTAINER -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">