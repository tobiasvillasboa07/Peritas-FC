-- Tablas adaptadas para SQLite

CREATE TABLE IF NOT EXISTS users (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(4))) || '-' || lower(hex(randomblob(2))) || '-4' || substr(lower(hex(randomblob(2))), 2) || '-' || substr('89ab', 1 + (abs(random()) % 4), 1) || substr(lower(hex(randomblob(2))), 2) || '-' || lower(hex(randomblob(6)))),
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    dni TEXT DEFAULT '',
    categoria TEXT NOT NULL DEFAULT 'Pleno (Acceso total)',
    role TEXT CHECK(role IN ('socio', 'admin')) NOT NULL DEFAULT 'socio',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS posts (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(4))) || '-' || lower(hex(randomblob(2))) || '-4' || substr(lower(hex(randomblob(2))), 2) || '-' || substr('89ab', 1 + (abs(random()) % 4), 1) || substr(lower(hex(randomblob(2))), 2) || '-' || lower(hex(randomblob(6)))),
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    category TEXT NOT NULL DEFAULT 'FUTBOL',
    image_url TEXT NULL,
    user_id TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS matches (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(4))) || '-' || lower(hex(randomblob(2))) || '-4' || substr(lower(hex(randomblob(2))), 2) || '-' || substr('89ab', 1 + (abs(random()) % 4), 1) || substr(lower(hex(randomblob(2))), 2) || '-' || lower(hex(randomblob(6)))),
    home_team TEXT NOT NULL DEFAULT 'PERITAS FC',
    away_team TEXT NOT NULL,
    tournament TEXT NOT NULL,
    stadium TEXT NOT NULL DEFAULT 'Estadio "Estrella Azul"',
    match_date DATETIME NOT NULL,
    is_home BOOLEAN NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tickets (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(4))) || '-' || lower(hex(randomblob(2))) || '-4' || substr(lower(hex(randomblob(2))), 2) || '-' || substr('89ab', 1 + (abs(random()) % 4), 1) || substr(lower(hex(randomblob(2))), 2) || '-' || lower(hex(randomblob(6)))),
    user_id TEXT NOT NULL,
    match_id TEXT NOT NULL,
    sector TEXT NOT NULL DEFAULT 'Tribuna General',
    price REAL NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS bot_faqs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    keyword TEXT NOT NULL UNIQUE,
    label TEXT NOT NULL,
    response_text TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS bot_messages (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(4))) || '-' || lower(hex(randomblob(2))) || '-4' || substr(lower(hex(randomblob(2))), 2) || '-' || substr('89ab', 1 + (abs(random()) % 4), 1) || substr(lower(hex(randomblob(2))), 2) || '-' || lower(hex(randomblob(6)))),
    user_id TEXT NULL,
    sender TEXT CHECK(sender IN ('user', 'bot')) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Inserts iniciales
INSERT OR IGNORE INTO bot_faqs (keyword, label, response_text) VALUES
('sede', '📍 Sede', 'Nuestra sede operativa se encuentra en la E.E.S.T. N° 4 de Berazategui (Calle 111 N° 1890). Teléfono: 4261-4796.'),
('asociarme', '💳 Asociarme', 'Podés asociarte haciendo clic en el botón "Asociarse" del menú superior o completando tus datos en el formulario digital.'),
('proximo_partido', '⚽ Próximo partido', 'El próximo partido es PERITAS FC vs ALIANZA DEPORTIVA en el Estadio Estrella Azul.');

INSERT INTO matches (home_team, away_team, tournament, stadium, match_date) VALUES
('PERITAS FC', 'ALIANZA DEPORTIVA', 'LIGA DE PRIMERA DIVISIÓN', 'Estadio Estrella Azul', '2026-07-19 17:00:00'),
('PERITAS FC', 'UNIÓN TECNOLÓGICA', 'COPA DE CAMPEONES', 'Estadio Estrella Azul', '2026-07-29 21:00:00');-- ============================================================
--  PERITAS FC - Portal de socios (SQLite)
--  Integridad de ENTIDAD   -> PRIMARY KEY en cada tabla
--  Integridad REFERENCIAL  -> FOREIGN KEY con ON DELETE / ON UPDATE
--  Integridad de DOMINIO   -> NOT NULL, UNIQUE, CHECK, DEFAULT
-- ============================================================
PRAGMA foreign_keys = ON;

-- ---------- categorias de socio (NUEVA) ----------
CREATE TABLE IF NOT EXISTS categorias (
    nombre        TEXT PRIMARY KEY,
    descripcion   TEXT NOT NULL DEFAULT '',
    cuota_mensual REAL NOT NULL DEFAULT 0 CHECK (cuota_mensual >= 0)
);

INSERT OR IGNORE INTO categorias (nombre, descripcion, cuota_mensual) VALUES
('Pleno (Acceso total)', 'Acceso libre a Tribuna Popular y beneficios completos', 5000),
('Adherente',            'Categoría básica de socio',                             3000);

-- ---------- users ----------
CREATE TABLE IF NOT EXISTS users (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(4))) || '-' || lower(hex(randomblob(2))) || '-4' || substr(lower(hex(randomblob(2))), 2) || '-' || substr('89ab', 1 + (abs(random()) % 4), 1) || substr(lower(hex(randomblob(2))), 2) || '-' || lower(hex(randomblob(6)))),
    name TEXT NOT NULL CHECK (length(trim(name)) >= 2),
    email TEXT NOT NULL UNIQUE CHECK (email LIKE '%_@_%._%'),
    password TEXT NOT NULL CHECK (length(password) >= 20),          -- hash de password_hash()
    dni TEXT UNIQUE CHECK (dni IS NULL OR (length(dni) BETWEEN 7 AND 8 AND dni NOT GLOB '*[^0-9]*')),
    categoria TEXT NOT NULL DEFAULT 'Pleno (Acceso total)'
        REFERENCES categorias(nombre) ON UPDATE CASCADE ON DELETE RESTRICT,
    role TEXT NOT NULL DEFAULT 'socio' CHECK (role IN ('socio', 'admin')),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ---------- posts (noticias) ----------
-- Si se borra el autor, la noticia se conserva (user_id queda en NULL)
CREATE TABLE IF NOT EXISTS posts (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(4))) || '-' || lower(hex(randomblob(2))) || '-4' || substr(lower(hex(randomblob(2))), 2) || '-' || substr('89ab', 1 + (abs(random()) % 4), 1) || substr(lower(hex(randomblob(2))), 2) || '-' || lower(hex(randomblob(6)))),
    title TEXT NOT NULL CHECK (length(trim(title)) > 0),
    content TEXT NOT NULL CHECK (length(trim(content)) > 0),
    category TEXT NOT NULL DEFAULT 'FUTBOL',
    image_url TEXT NULL,
    user_id TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ---------- matches ----------
CREATE TABLE IF NOT EXISTS matches (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(4))) || '-' || lower(hex(randomblob(2))) || '-4' || substr(lower(hex(randomblob(2))), 2) || '-' || substr('89ab', 1 + (abs(random()) % 4), 1) || substr(lower(hex(randomblob(2))), 2) || '-' || lower(hex(randomblob(6)))),
    home_team TEXT NOT NULL DEFAULT 'PERITAS FC',
    away_team TEXT NOT NULL,
    tournament TEXT NOT NULL,
    stadium TEXT NOT NULL DEFAULT 'Estadio Estrella Azul',
    match_date DATETIME NOT NULL,
    is_home INTEGER NOT NULL DEFAULT 1 CHECK (is_home IN (0, 1)),
    capacity INTEGER NOT NULL DEFAULT 500 CHECK (capacity > 0),     -- cupo de entradas
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (home_team <> away_team),
    UNIQUE (home_team, away_team, match_date)
);

-- ---------- tickets ----------
CREATE TABLE IF NOT EXISTS tickets (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(4))) || '-' || lower(hex(randomblob(2))) || '-4' || substr(lower(hex(randomblob(2))), 2) || '-' || substr('89ab', 1 + (abs(random()) % 4), 1) || substr(lower(hex(randomblob(2))), 2) || '-' || lower(hex(randomblob(6)))),
    user_id TEXT NOT NULL,
    match_id TEXT NOT NULL,
    sector TEXT NOT NULL DEFAULT 'Tribuna General',
    price REAL NOT NULL DEFAULT 0.00 CHECK (price >= 0),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    UNIQUE (user_id, match_id, sector)
);

-- ---------- cuotas (NUEVA) ----------
CREATE TABLE IF NOT EXISTS cuotas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,
    periodo TEXT NOT NULL CHECK (periodo GLOB '[0-9][0-9][0-9][0-9]-[0-1][0-9]'),   -- ej: 2026-10
    monto REAL NOT NULL CHECK (monto > 0),
    estado TEXT NOT NULL DEFAULT 'pendiente' CHECK (estado IN ('pendiente', 'pagada')),
    fecha_pago DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (user_id, periodo),
    CHECK ((estado = 'pagada') = (fecha_pago IS NOT NULL))
);

-- ---------- bot ----------
CREATE TABLE IF NOT EXISTS bot_faqs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    keyword TEXT NOT NULL UNIQUE,
    label TEXT NOT NULL,
    response_text TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS bot_messages (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(4))) || '-' || lower(hex(randomblob(2))) || '-4' || substr(lower(hex(randomblob(2))), 2) || '-' || substr('89ab', 1 + (abs(random()) % 4), 1) || substr(lower(hex(randomblob(2))), 2) || '-' || lower(hex(randomblob(6)))),
    user_id TEXT NULL,
    sender TEXT NOT NULL CHECK (sender IN ('user', 'bot')),
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------- updated_at automático ----------
CREATE TRIGGER IF NOT EXISTS trg_users_updated
AFTER UPDATE ON users
FOR EACH ROW WHEN NEW.updated_at = OLD.updated_at
BEGIN
    UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS trg_posts_updated
AFTER UPDATE ON posts
FOR EACH ROW WHEN NEW.updated_at = OLD.updated_at
BEGIN
    UPDATE posts SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- ---------- control de cupo en tickets ----------
CREATE TRIGGER IF NOT EXISTS trg_tickets_cupo
BEFORE INSERT ON tickets
FOR EACH ROW
WHEN (SELECT COUNT(*) FROM tickets WHERE match_id = NEW.match_id)
     >= (SELECT capacity FROM matches WHERE id = NEW.match_id)
BEGIN
    SELECT RAISE(ABORT, 'No quedan entradas disponibles para este partido');
END;

-- ---------- datos iniciales ----------
INSERT OR IGNORE INTO bot_faqs (keyword, label, response_text) VALUES
('sede', '📍 Sede', 'Nuestra sede operativa se encuentra en la E.E.S.T. N° 4 de Berazategui (Calle 111 N° 1890). Teléfono: 4261-4796.'),
('asociarme', '💳 Asociarme', 'Podés asociarte haciendo clic en el botón "Asociarse" del menú superior o completando tus datos en el formulario digital.'),
('proximo_partido', '⚽ Próximo partido', 'Podés ver el próximo partido y sacar tu entrada en la sección ENTRADAS.');

INSERT OR IGNORE INTO matches (home_team, away_team, tournament, stadium, match_date, capacity) VALUES
('PERITAS FC', 'ALIANZA DEPORTIVA', 'LIGA DE PRIMERA DIVISIÓN', 'Estadio Estrella Azul', '2026-11-08 17:00:00', 500),
('PERITAS FC', 'UNIÓN TECNOLÓGICA',  'COPA DE CAMPEONES',        'Estadio Estrella Azul', '2026-11-22 21:00:00', 500),
('PERITAS FC', 'DEPORTIVO SUR',      'LIGA DE PRIMERA DIVISIÓN', 'Estadio Estrella Azul', '2026-12-06 18:00:00', 500);