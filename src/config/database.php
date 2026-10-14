<?php
// Carga las variables de entorno si existen
$env = parse_ini_file(__DIR__ . '/../../.env');

// Ruta absoluta al archivo de la base de datos SQLite
$dbPath = __DIR__ . '/../../database.sqlite';

// DSN para SQLite
$dsn = "sqlite:$dbPath";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, null, null, $options);
} catch (\PDOException $e) {
    die("La conexión a la base de datos falló: " . $e->getMessage());
}
?>