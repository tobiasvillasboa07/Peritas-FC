# src: BACKEND

El código **PHP que corre en el servidor**. El navegador nunca ve este código: solo recibe el HTML o el JSON que genera.
Está ordenado como **MVC** (Modelo, Vista, Controlador):

| Carpeta | Qué hace | Archivos |
|---|---|---|
| `config/` | **Arranque** de cada página | `bootstrap.php` (hora, sesión y conexión), `database.php` (PDO a SQLite), `env.php` (lee el `.env`), `admin.php` (`requerir_admin()`) |
| `models/` | **Modelo:** las reglas del club y las consultas a la base | `mercadopago.php` (entradas, cuotas y pagos), `partidos.php`, `noticias.php`, `resultados.php`, `recuperar.php` (contraseña por mail), `foto_socio.php` |
| `controllers/` | **Controlador:** reciben una acción (un formulario o un `fetch`) y responden JSON o redirigen | `chatbot.php` (asistente), `pagos/` (`crear.php`, `retorno.php`, `webhook.php`), `posts/` (noticias del admin), `socios/foto.php`, `auth/logout.php` |
| `views/` | **Vista:** las páginas. Las ejecuta el servidor y le manda al navegador el HTML ya armado | `inicio.html`, `portal.php`, `entradas.php`, `plantel.php`, `resultados.php`, `auth/`, `admin/`, `posts/`, `_layouts/club.php` |

**Cómo se usan entre sí:** el navegador pide una vista o un controlador → ese archivo incluye `config/bootstrap.php`
(sesión y base) y el modelo que necesita (`require_once`) → el modelo consulta la base con PDO o llama a Mercado Pago
o EmailJS con cURL → la vista arma el HTML o el controlador responde JSON o redirige.

Lo que se manda tal cual al navegador (CSS, JavaScript e imágenes) está en [`assets/`](../assets).
