CREATE DATABASE IF NOT EXISTS peritasfc;

USE peritasfc;

CREATE TABLE IF NOT EXISTS users (
    id CHAR(36) NOT NULL DEFAULT (UUID()),
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    dni VARCHAR(20) NOT NULL,
    categoria VARCHAR(100) NOT NULL DEFAULT 'Pleno (Acceso total)',
    role ENUM('socio', 'admin') NOT NULL DEFAULT 'socio',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_dni (dni)
);
CREATE TABLE IF NOT EXISTS posts (
    id CHAR(36) NOT NULL DEFAULT (UUID()),
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'FUTBOL', 
    image_url VARCHAR(255) NULL,
    user_id CHAR(36) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS matches (
    id CHAR(36) NOT NULL DEFAULT (UUID()),
    home_team VARCHAR(100) NOT NULL DEFAULT 'PERITAS FC',
    away_team VARCHAR(100) NOT NULL,
    tournament VARCHAR(100) NOT NULL,
    stadium VARCHAR(100) NOT NULL DEFAULT 'Estadio "Estrella Azul"',
    match_date DATETIME NOT NULL,
    is_home BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
CREATE TABLE IF NOT EXISTS tickets (
    id CHAR(36) NOT NULL DEFAULT (UUID()),
    user_id CHAR(36) NOT NULL,
    match_id CHAR(36) NOT NULL,
    sector VARCHAR(50) NOT NULL DEFAULT 'Tribuna General',
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS bot_faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(50) NOT NULL UNIQUE,
    label VARCHAR(100) NOT NULL,
    response_text TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS bot_messages (
    id CHAR(36) NOT NULL DEFAULT (UUID()),
    user_id CHAR(36) NULL,
    sender ENUM('user', 'bot') NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
INSERT INTO bot_faqs (keyword, label, response_text) VALUES
('sede', '📍 Sede', 'Nuestra sede operativa se encuentra en la E.E.S.T. N° 4 de Berazategui (Calle 111 N° 1890). Teléfono: 4261-4796.'),
('asociarme', '💳 Asociarme', 'Podés asociarte haciendo clic en el botón "Asociarse" del menú superior o completando tus datos en el formulario digital.'),
('proximo_partido', '⚽ Próximo partido', 'El próximo partido es PERITAS FC vs ALIANZA DEPORTIVA en el Estadio Estrella Azul.');
INSERT INTO matches (id, home_team, away_team, tournament, stadium, match_date) VALUES
(UUID(), 'PERITAS FC', 'ALIANZA DEPORTIVA', 'LIGA DE PRIMERA DIVISIÓN', 'Estadio Estrella Azul', '2026-07-19 17:00:00'),
(UUID(), 'PERITAS FC', 'UNIÓN TECNOLÓGICA', 'COPA DE CAMPEONES', 'Estadio Estrella Azul', '2026-07-29 21:00:00');