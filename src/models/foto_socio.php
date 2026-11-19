<?php
/**
 * Foto del socio para la credencial del portal.
 *
 * Se guarda DENTRO de la base de datos (tabla fotos_socios) y no como archivo:
 * en Render el disco se borra cada vez que el servidor se reinicia, pero la base
 * no se pierde porque Litestream la copia a Backblaze.
 * Antes de subirla, el navegador la achica a 400 px de ancho (ver portal.php), así pesa poco.
 */

const FOTO_MAX_BYTES = 2 * 1024 * 1024;                          // 2 MB
const FOTO_TIPOS     = ['image/jpeg', 'image/png', 'image/webp'];

// Tabla de fotos (se crea sola si la base ya existía antes de este cambio)
$pdo->exec("CREATE TABLE IF NOT EXISTS fotos_socios (
    user_id TEXT PRIMARY KEY,
    tipo TEXT NOT NULL CHECK (tipo IN ('image/jpeg', 'image/png', 'image/webp')),
    datos BLOB NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Revisa la foto (máx. 2 MB, imagen real con getimagesize) y la guarda como BLOB (la llama socios/foto.php)
function guardar_foto_socio(PDO $pdo, string $userId, array $archivo): void {
    $error = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE || ($archivo['size'] ?? 0) > FOTO_MAX_BYTES) {
        throw new InvalidArgumentException('La foto tiene que pesar menos de 2 MB.');
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('No se pudo subir la foto. Probá con otra (JPG o PNG).');
    }
    // Miramos el contenido real del archivo, no el nombre ni lo que dice el navegador:
    // si no es una imagen de verdad, no se guarda
    $info = @getimagesize($archivo['tmp_name']);
    if (!$info || !in_array($info['mime'], FOTO_TIPOS, true)) {
        throw new InvalidArgumentException('La foto tiene que ser una imagen JPG, PNG o WebP.');
    }

    $st = $pdo->prepare('INSERT OR REPLACE INTO fotos_socios (user_id, tipo, datos) VALUES (?, ?, ?)');
    $st->bindValue(1, $userId);
    $st->bindValue(2, $info['mime']);
    $st->bindValue(3, file_get_contents($archivo['tmp_name']), PDO::PARAM_LOB);
    $st->execute();
}

// Devuelve la foto como data URI para el <img> del carnet, o null (la usa portal.php)
function foto_socio(PDO $pdo, string $userId): ?string {
    $st = $pdo->prepare('SELECT tipo, datos FROM fotos_socios WHERE user_id = ?');
    $st->execute([$userId]);
    $foto = $st->fetch();
    return $foto ? 'data:' . $foto['tipo'] . ';base64,' . base64_encode($foto['datos']) : null;
}
// Borra la foto del socio (botón Quitar)
function borrar_foto_socio(PDO $pdo, string $userId): void {
    $pdo->prepare('DELETE FROM fotos_socios WHERE user_id = ?')->execute([$userId]);
}
