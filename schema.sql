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
('PERITAS FC', 'UNIÓN TECNOLÓGICA', 'COPA DE CAMPEONES', 'Estadio Estrella Azul', '2026-07-29 21:00:00');