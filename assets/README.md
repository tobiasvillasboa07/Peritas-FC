# assets: FRONTEND

Archivos que el **navegador descarga tal cual**: el servidor no los ejecuta, solo los manda.

| Carpeta | Qué hay | Quién lo usa |
|---|---|---|
| `css/dala.css` | Colores (negro, violeta y ámbar), tipografía y bordes del club. Se carga después de Tailwind. | Todas las páginas, con `<link href="/assets/css/dala.css">` |
| `js/asistente.js` | Chat flotante del asistente. Le manda los mensajes a `src/controllers/chatbot.php` con `fetch` y muestra la respuesta JSON. | `src/views/portal.php` y el pie compartido `src/views/_layouts/club.php` (plantel, resultados y admin). El inicio tiene su propio chat dentro de `src/views/inicio.html`. |
| `img/` | Escudo, fotos de noticias, `plantel/` (jugadores) y `partidos/` (fondo según el rival). | Las páginas, con `<img src="/assets/img/...">` |
| `data/` | JSON que se editan sin tocar código: `partidos.json` (partidos y precios), `resultados.json`, `plantel.json`, `noticias.json`. | El **backend** (PHP) los lee y los copia a la base. `noticias.json` también lo puede pedir el JavaScript del inicio si no le llegaron las noticias. |

El código que corre en el servidor (backend) está en [`src/`](../src).
