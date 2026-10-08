<?php
require_once __DIR__ . '/_layouts/layout.php';
?>

<!-- VISTA PRINCIPAL (INICIO) -->
<div class="space-y-12">
    <!-- Hero Banner: Próximo Partido -->
    <div class="relative rounded-3xl overflow-hidden bg-club-navy border border-club-blue/50 shadow-2xl">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: linear-gradient(to right, rgba(11, 19, 43, 0.95) 40%, rgba(11, 19, 43, 0.3) 100%), url('http://googleusercontent.com/image_collection/image_retrieval/5458801617881165471_0');"></div>
        
        <div class="relative z-10 px-6 sm:px-12 py-16 sm:py-24 max-w-2xl space-y-6">
            <span class="inline-flex items-center gap-2 px-3 py-1 text-xs font-bold bg-club-gold/20 text-club-gold border border-club-gold/40 rounded-full tracking-widest uppercase">
                <span class="w-2 h-2 rounded-full bg-club-gold animate-ping"></span> PRÓXIMO PARTIDO DE LOCAL
            </span>
            <h1 class="text-4xl sm:text-5xl font-black text-white leading-tight">PERITAS FC <span class="text-club-gold text-2xl sm:text-3xl block mt-1">VS</span> ALIANZA DEPORTIVA</h1>
            <p class="text-slate-300 text-sm sm:text-base leading-relaxed">
                Este domingo volvemos a casa. Vení a alentar al gigante de la tecnología en el Estadio "Estrella Azul". Asegurá tu lugar antes de que se agoten las entradas.
            </p>
            
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 pt-4">
                <div class="flex gap-3">
                    <div class="text-center bg-slate-900/80 backdrop-blur-sm border border-club-blue/50 p-3 rounded-xl min-w-[70px]">
                        <span id="days" class="block text-2xl font-bold text-white">03</span>
                        <span class="text-[10px] text-slate-400 uppercase tracking-wider">Días</span>
                    </div>
                    <div class="text-center bg-slate-900/80 backdrop-blur-sm border border-club-blue/50 p-3 rounded-xl min-w-[70px]">
                        <span id="hours" class="block text-2xl font-bold text-white">14</span>
                        <span class="text-[10px] text-slate-400 uppercase tracking-wider">Horas</span>
                    </div>
                    <div class="text-center bg-slate-900/80 backdrop-blur-sm border border-club-blue/50 p-3 rounded-xl min-w-[70px]">
                        <span id="mins" class="block text-2xl font-bold text-white">25</span>
                        <span class="text-[10px] text-slate-400 uppercase tracking-wider">Mins</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección Sede E.E.S.T. N° 4 Berazategui -->
    <div class="space-y-6">
        <div class="border-b border-club-blue/30 pb-4">
            <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-map-location-dot text-club-gold"></i> Nuestra Sede y Dirección Técnica
            </h2>
            <p class="text-xs text-slate-400 mt-1">Conocé dónde entrenamos, gestionamos el club y planificamos cada partido</p>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 bg-club-navy/50 border border-club-blue/30 rounded-3xl p-6 sm:p-8 backdrop-blur-sm relative overflow-hidden">
            <div class="absolute -left-16 -bottom-16 w-48 h-48 bg-club-gold/5 rounded-full blur-3xl"></div>
            
            <div class="lg:col-span-5 space-y-6 flex flex-col justify-between">
                <div class="space-y-4">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-[10px] font-black bg-club-gold/20 text-club-gold border border-club-gold/40 rounded-full tracking-wider uppercase">
                        <i class="fa-solid fa-school text-xs"></i> E.E.S.T. N° 4 de Berazategui
                    </span>
                    <h3 class="text-xl font-extrabold text-white uppercase">Predio de Entrenamiento y Dirección Técnica</h3>
                    <p class="text-slate-300 text-sm leading-relaxed">
                        En alianza estratégica de fútbol y formación técnica, la sede operativa y dirección de entrenamiento de <strong class="text-club-gold">Peritas FC</strong> se establece en la destacada Escuela de Educación Secundaria Técnica N° 4 de Berazategui.
                    </p>
                    
                    <div class="space-y-3 pt-2 text-sm text-slate-300">
                        <p class="flex items-start gap-3">
                            <i class="fa-solid fa-location-dot text-club-gold mt-1"></i>
                            <span><strong>Dirección:</strong> Calle 111 N° 1890 entre 18 y 19, Berazategui, Buenos Aires (CP 1884).</span>
                        </p>
                        <p class="flex items-center gap-3">
                            <i class="fa-solid fa-phone text-club-gold"></i>
                            <span><strong>Teléfono Sede:</strong> 4261-4796</span>
                        </p>
                    </div>
                </div>
                
                <div class="pt-4 border-t border-club-blue/20 flex flex-wrap gap-4 items-center justify-between">
                    <a href="https://maps.google.com/?q=-34.785804,-58.243205" target="_blank" class="px-4 py-2 text-xs bg-club-blue hover:bg-club-gold hover:text-club-dark text-slate-200 font-bold rounded-xl transition flex items-center gap-1.5">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir en Maps
                    </a>
                </div>
            </div>
            
            <div class="lg:col-span-7 h-[350px] sm:h-[400px] rounded-2xl overflow-hidden border border-club-gold/30 shadow-2xl relative">
                <iframe 
                    src="https://maps.google.com/maps?q=-34.785804,-58.243205&z=16&t=m&output=embed" 
                    class="w-full h-full relative z-10 border-0"
                    allowfullscreen="" 
                    loading="lazy">
                </iframe>
            </div>
        </div>
    </div>
</div>

</main>
<footer class="hidden md:block bg-club-dark border-t border-club-blue/20 py-8 text-center text-slate-400 text-xs">
    <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-4">
        <p>© 2026 Peritas FC. El club de la tecnología. Todos los derechos reservados.</p>
    </div>
</footer>
</body>
</html>