<?php
// Conexión a SQLite (el archivo database.sqlite se crea solo si no existe)
$dbPath     = __DIR__ . '/../../database.sqlite';
$schemaPath = __DIR__ . '/../../schema.sql';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO("sqlite:$dbPath", null, null, $options);

    // SQLite ignora las claves foráneas si no se activan en CADA conexión
    $pdo->exec('PRAGMA foreign_keys = ON;');

    // Si la base está vacía (por ejemplo en un Codespace nuevo), crea las tablas
    $hayTablas = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'users'")->fetchColumn();
    if (!$hayTablas && is_readable($schemaPath)) {
        $pdo->exec(file_get_contents($schemaPath));
    }
} catch (PDOException $e) {
    die('La conexión a la base de datos falló: ' . $e->getMessage());
}