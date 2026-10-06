<?php
/**
 * comentar.php — Handler de creación de comentarios
 * Paso B (Agente 2: Backend Dev Agent) — D-02, D-06, D-09 · F-COM-001 CA-1 a CA-6
 *
 * Flujo (D-09: POST → Redirect → GET):
 *   - Método distinto de POST → 405.
 *   - Validación fallida      → 422 re-render de la página con el mensaje junto
 *                               al formulario y los valores conservados.
 *   - Éxito                   → 303 a index.php#comentario-{id} (sin mensaje).
 *   - Fallo interno           → 500 genérico (detalle solo en el log, D-06).
 */

declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/validacion.php';
require __DIR__ . '/comentarios.php';
require __DIR__ . '/respuestas.php'; // módulo de consulta: cargar_conversacion() agrupa respuestas
require __DIR__ . '/vistas.php';

// --- 405: solo se acepta POST -------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Método no permitido</title></head>';
    echo '<body><p>Método no permitido. <a href="index.php">Volver</a></p></body></html>';
    exit;
}

// --- Lectura de la entrada (el cliente no es la fuente de verdad, D-02) --
// Solo se leen autor y contenido: cualquier fecha enviada se ignora (RN-007).
$autor     = (string) ($_POST['autor'] ?? '');
$contenido = (string) ($_POST['contenido'] ?? '');

// --- Validación en el servidor (D-02) ------------------------------------
$error = validar_publicacion($autor, $contenido, 'comentario');

// --- 422: re-render con mensaje y valores conservados (D-09) -------------
if ($error !== null) {
    http_response_code(422);
    [$comentarios, $agrupadas] = cargar_conversacion(db_conexion());
    dibujar_pagina($comentarios, $agrupadas, $error, $autor, $contenido);
    exit;
}

// --- Persistencia y 303 (CA-1, CA-6) ------------------------------------
try {
    $id = guardar_comentario(db_conexion(), recortar($autor), recortar($contenido));
} catch (Throwable $e) {
    error_log('[comentar.php] ' . $e->getMessage());
    http_response_code(500);
    [$comentarios, $agrupadas] = cargar_conversacion(db_conexion());
    dibujar_pagina($comentarios, $agrupadas, 'Ocurrió un error interno. Tu comentario no se guardó.', $autor, $contenido);
    exit;
}

header('Location: index.php#comentario-' . $id, true, 303);
exit;
