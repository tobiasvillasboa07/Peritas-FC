(function () {
    const user = window.PFC_USER || null;
    const nombre = user ? user.name : 'Invitado';
    const email = user ? user.email : 'invitado@peritas.com';

    // Íconos de Font Awesome (el mismo robot que usa la página de inicio)
    if (!document.querySelector('link[href*="font-awesome"]')) {
        document.head.insertAdjacentHTML('beforeend', '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">');
    }

    const html = `
    <div id="chatbot-wrapper" class="fixed bottom-6 right-4 md:right-6 z-50 flex flex-col items-end">
        <div id="chatbot-badge-prompt" onclick="openChatbotModal()" class="cursor-pointer bg-gradient-to-r from-[#ffb829] via-yellow-400 to-[#ffb829] text-[#000000] text-xs font-black px-3.5 py-2 rounded-2xl shadow-xl border-2 border-yellow-200 mb-2 flex items-center gap-2 animate-bounce hover:scale-105 transition">
            <i class="fa-solid fa-robot text-base"></i>
            <span>¡Hola! ¿Dudas sobre el club? Preguntame</span>
            <i class="fa-solid fa-chevron-right text-[10px]"></i>
        </div>
        <div id="chatbot-modal" class="hidden w-[340px] sm:w-[380px] h-[480px] bg-[#0a0a0a] border-2 border-[#ffb829]/60 rounded-3xl shadow-2xl flex flex-col overflow-hidden mb-3">
            <div class="bg-gradient-to-r from-[#000000] via-[#0a0a0a] to-[#2a2a2a] p-4 border-b border-[#2a2a2a]/40 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-[#ffb829] flex items-center justify-center text-[#000000] text-sm shadow"><i class="fa-solid fa-robot"></i></div>
                    <div>
                        <h4 class="text-sm font-bold text-white leading-tight">Asistente Virtual PFC</h4>
                        <p id="chatbot-user-indicator" class="text-[10px] text-[#ffb829] font-medium"></p>
                    </div>
                </div>
                <button type="button" onclick="toggleChatbot()" class="w-7 h-7 rounded-full bg-[#2a2a2a]/50 text-slate-300 hover:text-white hover:bg-[#2a2a2a] flex items-center justify-center transition"><i class="fa-solid fa-xmark text-xs"></i></button>
            </div>
            <div id="chatbot-messages" class="flex-grow p-4 overflow-y-auto space-y-3 bg-slate-950/60 text-xs">
                <div class="bg-[#2a2a2a]/40 text-slate-200 border border-[#2a2a2a]/40 p-3 rounded-2xl rounded-tl-none max-w-[85%] space-y-1">
                    <p class="font-bold text-[#ffb829] text-[10px] uppercase">Asistente Peritas FC</p>
                    <p>¡Hola! Soy tu asistente. ¿En qué puedo ayudarte hoy?</p>
                </div>
            </div>
            <div class="p-2 bg-[#000000]/80 border-t border-[#2a2a2a]/20 flex gap-1.5 overflow-x-auto text-[11px]">
                <button type="button" onclick="sendQuickMessage('¿Dónde queda la sede?')" class="whitespace-nowrap px-2.5 py-1 bg-[#2a2a2a]/40 hover:bg-[#ffb829] hover:text-[#000000] text-slate-300 rounded-full border border-[#2a2a2a]/50 transition">📍 Sede</button>
                <button type="button" onclick="sendQuickMessage('¿Cómo me asocio?')" class="whitespace-nowrap px-2.5 py-1 bg-[#2a2a2a]/40 hover:bg-[#ffb829] hover:text-[#000000] text-slate-300 rounded-full border border-[#2a2a2a]/50 transition">💳 Asociarme</button>
                <button type="button" onclick="sendQuickMessage('¿Cuándo juegan?')" class="whitespace-nowrap px-2.5 py-1 bg-[#2a2a2a]/40 hover:bg-[#ffb829] hover:text-[#000000] text-slate-300 rounded-full border border-[#2a2a2a]/50 transition">⚽ Próximo partido</button>
            </div>
            <div class="p-3 bg-[#0a0a0a] border-t border-[#2a2a2a]/40 flex items-center gap-2">
                <input type="text" id="chatbot-input" placeholder="Escribí un mensaje..." class="flex-grow bg-slate-900 border border-[#2a2a2a]/50 px-3 py-2 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-[#ffb829]">
                <button type="button" onclick="sendChatMessage()" class="w-9 h-9 bg-gradient-to-r from-[#ffb829] to-yellow-500 text-[#000000] font-bold rounded-xl flex items-center justify-center hover:scale-105 transition shadow-md"><i class="fa-solid fa-paper-plane text-xs"></i></button>
            </div>
        </div>
        <button type="button" onclick="toggleChatbot()" class="w-14 h-14 rounded-full bg-gradient-to-r from-[#ffb829] to-yellow-500 text-[#000000] text-xl shadow-2xl flex items-center justify-center hover:scale-110 transition relative border-2 border-[#000000] group">
            <i class="fa-solid fa-comments group-hover:rotate-12 transition transform"></i>
            <span class="absolute -top-1 -right-1 w-4 h-4 bg-emerald-500 rounded-full border-2 border-[#000000] animate-ping"></span>
            <span class="absolute -top-1 -right-1 w-4 h-4 bg-emerald-500 rounded-full border-2 border-[#000000]"></span>
        </button>
    </div>`;


document.body.insertAdjacentHTML('beforeend', html);
    document.getElementById('chatbot-user-indicator').innerText = user ? `${nombre} (${email})` : 'Usuario: Invitado';
    document.getElementById('chatbot-input').addEventListener('keypress', e => { if (e.key === 'Enter') sendChatMessage(); });

    function escapeHtml(str) {
        return str.replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
    }

    function botReply(text) {
        const msg = text.toLowerCase();
        if (msg.includes('hola') || msg.includes('buenas')) return `¡Hola ${nombre}! ⚽ Bienvenido al Portal de Peritas FC. ¿En qué te puedo asesorar hoy?`;
        if (msg.includes('asociar') || msg.includes('socio') || msg.includes('cuota')) return `Podés asociarte ingresando a la pestaña 'PORTAL SOCIO'. La cuota Pleno es de AR$ 5.000/mes con acceso libre a Tribuna Popular.`;
        if (msg.includes('partido') || msg.includes('proximo') || msg.includes('próximo') || msg.includes('juegan')) return `El próximo domingo jugamos contra Alianza Deportiva en el Estadio Estrella Azul a las 17:00 hs.`;
        if (msg.includes('entrada') || msg.includes('ticket') || msg.includes('comprar')) return `Podés adquirir tus entradas en la sección 'ENTRADAS'. ¡Los socios con cuota al día entran gratis!`;
        if (msg.includes('donde') || msg.includes('dónde') || msg.includes('sede') || msg.includes('ubicacion')) return `Nuestra sede y centro de entrenamiento está en la E.E.S.T. N° 4 de Berazategui (Calle 111 N° 1890 entre 18 y 19).`;
        if (msg.includes('chau') || msg.includes('adios') || msg.includes('gracias')) return `¡Muchas gracias ${nombre}! Vamos Peritas FC 💙💛`;
        return `No entendí bien tu consulta 🤔. Podés preguntarme sobre cuotas, asociarte, el próximo partido o la sede del club.`;
    }

    function appendBot(text) {
        const box = document.getElementById('chatbot-messages');
        box.insertAdjacentHTML('beforeend', `
            <div class="bg-[#2a2a2a]/40 text-slate-200 border border-[#2a2a2a]/40 p-3 rounded-2xl rounded-tl-none max-w-[85%] space-y-1 shadow">
                <p class="font-bold text-[#ffb829] text-[10px] uppercase">Asistente Peritas FC</p>
                <p>${text}</p>
            </div>`);
        box.scrollTop = box.scrollHeight;
    }


        window.toggleChatbot = function () {
        const abierto = !document.getElementById('chatbot-modal').classList.toggle('hidden');
        document.getElementById('chatbot-badge-prompt').classList.toggle('hidden', abierto);
    };
    window.openChatbotModal = function () {
        document.getElementById('chatbot-modal').classList.remove('hidden');
        document.getElementById('chatbot-badge-prompt').classList.add('hidden');
    };

    window.sendQuickMessage = function (text) {
        document.getElementById('chatbot-input').value = text;
        sendChatMessage();
    };
    window.sendChatMessage = function () {
        const input = document.getElementById('chatbot-input');
        const text = input.value.trim();
        if (!text) return;
        const box = document.getElementById('chatbot-messages');
        box.insertAdjacentHTML('beforeend', `
            <div class="bg-gradient-to-r from-[#ffb829] to-yellow-500 text-[#000000] font-medium p-3 rounded-2xl rounded-tr-none ml-auto max-w-[85%] space-y-0.5 shadow-md">
                <p class="font-bold text-[10px] uppercase opacity-75">Tú (${escapeHtml(nombre)})</p>
                <p>${escapeHtml(text)}</p>
            </div>`);
        input.value = '';
        box.scrollTop = box.scrollHeight;
                // Respuesta del servidor (src/controllers/chatbot.php); si falla, contesta el navegador
        fetch('/src/controllers/chatbot.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'mensaje=' + encodeURIComponent(text)
        })
            .then(res => res.json())
            .then(data => appendBot(data.respuesta))
            .catch(() => appendBot(botReply(text)));
    };
})();