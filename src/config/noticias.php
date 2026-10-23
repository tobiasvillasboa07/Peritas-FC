<?php
/**
 * Noticias del club: se guardan en la tabla posts (las carga el admin)
 * y la página de inicio las lee desde acá.
 */

const CATEGORIAS_NOTICIAS = ['futbol' => 'Fútbol', 'institucional' => 'Institucional'];
const IMAGEN_NOTICIA_DEFAULT = '/assets/img/noticia-default.svg';

/** La primera vez, pasa las noticias de assets/data/noticias.json a la base. */
function sembrar_noticias(PDO $pdo): void {
    if ($pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn() > 0) return;
    $archivo = __DIR__ . '/../../assets/data/noticias.json';
    $lista = is_readable($archivo) ? json_decode(file_get_contents($archivo), true) : null;
    if (!is_array($lista)) return;

    $ins = $pdo->prepare('INSERT INTO posts (title, content, category, image_url, user_id, created_at, updated_at)
                          VALUES (?, ?, ?, ?, NULL, ?, ?)');
    foreach ($lista as $i => $n) {
        // "Hoy" = hoy, "Ayer" = 1 día, "Hace 3 días" = 3 días; si no, una por día
        $dias = preg_match('/(\d+)/', $n['date'] ?? '', $m) ? (int)$m[1] : (($n['date'] ?? '') === 'Ayer' ? 1 : (($n['date'] ?? '') === 'Hoy' ? 0 : $i));
        $fecha = gmdate('Y-m-d H:i:s', time() - $dias * 86400 - ($i + 1) * 60);   // un poco antes, así lo nuevo queda arriba
        $ins->execute([$n['title'] ?? 'Noticia', $n['summary'] ?? '', $n['category'] ?? 'futbol', $n['img'] ?? null, $fecha, $fecha]);
    }
}

/** "Hoy", "Ayer", "Hace 3 días" o la fecha, a partir del created_at (guardado en UTC). */
function fecha_relativa(string $creado): string {
    $dias = (int)floor((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($creado . ' UTC')))) / 86400);
    if ($dias <= 0) return 'Hoy';
    if ($dias === 1) return 'Ayer';
    if ($dias < 7) return "Hace $dias días";
    return date('d/m/Y', strtotime($creado . ' UTC'));
}

/** Noticias listas para la página de inicio (mismo formato que tenía noticias.json). */
function noticias_para_pagina(PDO $pdo): array {
    $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    $filas = $pdo->query('SELECT id, title, content, category, image_url, created_at FROM posts ORDER BY created_at DESC')->fetchAll();
    return array_map(fn($p) => [
        'id'       => $p['id'],
        'category' => $e(strtolower($p['category'])),
        'title'    => $e($p['title']),
        'summary'  => $e($p['content']),
        'date'     => fecha_relativa($p['created_at']),
        'img'      => $e($p['image_url'] ?: IMAGEN_NOTICIA_DEFAULT),
    ], $filas);
}

/** Imágenes disponibles en assets/img para elegir en el formulario del admin. */
function imagenes_disponibles(): array {
    $archivos = glob(__DIR__ . '/../../assets/img/*.{jpg,jpeg,png,webp,svg}', GLOB_BRACE) ?: [];
    return array_map(fn($f) => '/assets/img/' . basename($f), $archivos);
}