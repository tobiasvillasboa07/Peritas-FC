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
            <a href="/src/views/index.php" class="flex items-center gap-3 group">
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

            <!-- Desktop Nav Links -->
            <nav class="hidden md:flex items-center gap-8 font-semibold text-sm tracking-wide text-slate-300">
                <a href="/src/views/index.php" class="hover:text-club-gold transition py-2 border-b-2 border-transparent active-tab">INICIO</a>
                <a href="/src/views/posts/index.php" class="hover:text-club-gold transition py-2 border-b-2 border-transparent">NOTICIAS</a>
            </nav>

            <!-- User Auth Corner -->
            <div class="hidden md:flex items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <p class="text-sm font-semibold text-white"><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Socio'); ?></p>
                        <p class="text-xs text-club-gold">Socio Activo</p>
                    </div>
                    <a href="/src/controllers/auth/logout.php" class="p-2 text-slate-400 hover:text-red-400 transition" title="Cerrar Sesión">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- MAIN CONTAINER -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">