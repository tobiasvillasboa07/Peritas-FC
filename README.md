# Peritas FC — Portal de socios

Proyecto de PDISC · 7° Año 4° División · E.E.S.T. N° 4 de Berazategui.

Página del club **Peritas FC** ("el club de la tecnología"): noticias, venta de entradas con Mercado Pago,
portal del socio con cuota mensual, plantel, resultados y tabla de posiciones, asistente virtual y
panel de administración.

**Página publicada:** https://peritasfc.onrender.com

## Integrantes

- Curia Tiziano
- Maccartney Santiago
- Vera Benjamín
- Vera Thiago
- Villasboa Tobías

## Qué tiene la página

| Sección | Qué hace |
|---|---|
| **Inicio** | Banner con el próximo partido (con foto del rival) y cuenta regresiva real, noticias, sede del club y asistente virtual. |
| **Noticias** | Noticias del club con filtro Fútbol / Institucional. Las carga el admin. |
| **Entradas** | Compra de entradas por sector con **Mercado Pago**. Los socios Pleno con la cuota al día entran gratis a la Tribuna Popular. |
| **Plantel** | Jugadores del primer equipo por posición, con número y foto (se cargan desde un JSON). |
| **Resultados** | Últimos partidos de Peritas FC y **tabla de posiciones** calculada sola (3 puntos por ganar, 1 por empatar). |
| **Portal del socio** | Credencial, cuotas mensuales (se pagan con Mercado Pago), mis entradas e **historial de pagos**. |
| **Asistente** | Responde con datos reales: próximo partido, precios, cuota del socio, cómo asociarse y la sede. |
| **Administración** | **Estadísticas** (socios, recaudación, ocupación por partido, deudores), partidos, resultados y noticias. Solo para usuarios con rol `admin`. |
| **Cuenta** | Registro (queda logueado al crear la cuenta), login, "¿Olvidaste tu contraseña?" (con email + DNI) y salir. Si se vuelve al login con el botón "Atrás", la sesión se cierra. |

## Cómo están ordenados los archivos

El código sigue la idea de **MVC** (Modelo, Vista, Controlador), sin framework:

```
index.php            Entrada de la página: arma el inicio con los datos de la base
router.php           "Portero" del servidor de PHP: no deja descargar la base ni el .env
.htaccess            Lo mismo que router.php, pero para Apache (XAMPP)
schema.sql           Tablas de la base de datos (se crean solas la primera vez)
Dockerfile           Publicación: arma la "caja" con PHP para Render
run.sh               Publicación: lo que se ejecuta al prender el servidor en Render
litestream.yml       Publicación: dónde se guarda la copia de la base (Backblaze B2)
.env.example         Qué variables secretas hacen falta (el .env real no se sube)

assets/              Lo que se manda tal cual al navegador
  css/dala.css         Colores y tipografía del club
  js/asistente.js      Chat flotante del asistente (portal, plantel, resultados)
  data/                Datos que se editan sin tocar código (partidos, resultados, plantel, noticias)
  img/                 Escudo y fotos de las noticias
  img/plantel/         Fotos de los jugadores
  img/partidos/        Fotos de fondo de los partidos (una por rival)

src/                 El código PHP (lo que corre en el servidor)
  config/              Arranque: hora, sesión, conexión a la base, .env y control de admin
  models/              Datos y reglas del club: pagos y cuotas (Mercado Pago), partidos, noticias y resultados
  controllers/         Reciben una acción y responden: pagos, asistente, noticias del admin y cerrar sesión
  views/               Las páginas que se ven
    inicio.html          Inicio, noticias y entradas (una sola página con JavaScript)
    portal.php           Portal del socio
    entradas.php         Comprar entradas (versión sin JavaScript)
    plantel.php          Plantel
    resultados.php       Resultados y tabla de posiciones
    auth/                Login, registro y recuperar contraseña
    admin/               Estadísticas, partidos y resultados (solo admin)
    posts/               Noticias del admin (listado y formulario)
    _layouts/club.php    Encabezado y pie compartidos
```

## Tecnologías

- **PHP 8** sin frameworks, con **PDO**.
- **SQLite** como base de datos (`database.sqlite`, se crea sola con `schema.sql`).
- **Tailwind CSS** (desde CDN) + `assets/css/dala.css` con los colores del club (negro, violeta y ámbar).
- **JavaScript** sin librerías para la página de inicio, el asistente y la cuenta regresiva.
- **Mercado Pago Checkout Pro** para cobrar entradas y cuotas (con cURL, sin librerías).
- **Docker + Render** para publicar, y **Litestream** para guardar la base en la nube (Backblaze B2).

## Cómo correrlo (Codespaces o tu compu)

1. Clonar el repo (o abrir un Codespace desde GitHub: **Code → Codespaces → Create codespace on main**).
2. Copiar `.env.example` como `.env` y poner el token de prueba de Mercado Pago:
   ```
   MP_ACCESS_TOKEN=TEST-tu-token
   ```
   El `.env` **no se sube a GitHub** (está en `.gitignore`).
3. Levantar el servidor (con el "portero" `router.php`):
   ```bash
   php -S 0.0.0.0:8000 router.php
   ```
4. Abrir la página: en Codespaces, pestaña **Ports** → puerto 8000. En tu compu, http://localhost:8000

La base `database.sqlite` se crea sola la primera vez. Si cambia `schema.sql`, borrarla (`rm database.sqlite`) para que se vuelva a crear.

Para que un usuario sea **admin**: en Render se pone su email en la variable `ADMIN_EMAIL`. En local:
```bash
php -r '$p=new PDO("sqlite:database.sqlite"); $p->exec("UPDATE users SET role=\"admin\" WHERE email=\"tu@mail.com\"");'
```

## Datos y fotos que se cambian sin tocar código

| Dónde | Para qué |
|---|---|
| `assets/data/partidos.json` | Próximos partidos y **precios de cada sector**. Al cambiarlo se actualiza la página sola. |
| `assets/data/resultados.json` | Resultados del torneo (los que vienen son **de ejemplo**). El admin también carga resultados desde el panel. En "Últimos partidos" se ven solo los de Peritas FC; los de otros equipos se usan para la tabla. |
| `assets/data/plantel.json` | Jugadores: número, nombre, posición y foto (por ejemplo `"/assets/img/plantel/jugador-9.jpg"`; vacío muestra el escudo). |
| `assets/data/noticias.json` | Noticias iniciales. Después se cargan desde **Admin → Noticias**. |
| `assets/img/partidos/` | Fotos de fondo de los partidos. Se llaman como el rival: `racing-digital.jpg`, `alianza-deportiva.jpg`. Si un rival no tiene foto se usa `general.jpg`, y si tampoco está, el fondo de cancha de siempre. |

## Pagos con Mercado Pago

1. El botón **Pagar** llama a `src/controllers/pagos/crear.php` (entradas) o al portal (cuotas). El precio se calcula **en el servidor**.
2. Se guarda un pago "pendiente" y se crea la preferencia en Mercado Pago.
3. El usuario paga y vuelve a `retorno.php`, que **le pregunta a Mercado Pago** si el pago se aprobó. Recién ahí se guarda la entrada o se marca la cuota como pagada.
4. `webhook.php` recibe los avisos de Mercado Pago (necesita una URL pública https, como la de Render).

Para probar: token de prueba, **tarjetas de prueba** de Mercado Pago y ventana de incógnito.

## Publicación en Render

- Render arma la imagen con el `Dockerfile` y ejecuta `run.sh`.
- `run.sh` baja la base guardada en Backblaze (Litestream), la crea si no existe, da rol admin a `ADMIN_EMAIL` y arranca el servidor con `router.php`.
- Variables de entorno en Render (**Environment**): `MP_ACCESS_TOKEN`, `ADMIN_EMAIL`, `LITESTREAM_ACCESS_KEY_ID`, `LITESTREAM_SECRET_ACCESS_KEY`.
- En el plan gratis la página "se duerme" a los 15 minutos sin visitas y tarda unos segundos en despertar.

## Seguridad

- Contraseñas guardadas con `password_hash` y comprobadas con `password_verify`.
- Consultas con PDO y parámetros (sin SQL armado a mano con datos del usuario).
- Sesiones en todas las páginas privadas; las de administración controlan que el usuario sea `admin`.
- El precio y el estado de cada pago se verifican en el servidor contra Mercado Pago.
- El token de Mercado Pago y las claves de Litestream van en variables de entorno, nunca en el repo.
- `router.php` y `.htaccess` no dejan descargar la base de datos, el `.env` ni los archivos de configuración.
