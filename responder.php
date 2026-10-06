<?php
/**
 * responder.php — Handler de creación de respuestas
 * F-RES-001 (Agente 2: Backend Dev Agent) — D-02, D-03, D-09 · RN-004, RN-006, RN-007
 *
 * Flujo (D-09: POST → Redirect → GET):
 *   - Método distinto de POST → 405.
 *   - Validación de campos (orden D-02) → 422 re-render con mensajes §7
 *     ("La respuesta ...") y valores conservados.
 *   - Padre inexistente → 404 con "El comentario que intentas responder no existe"
 *     y sin guardar nada (F-RES-001 CA-6). Un id de respuesta tampoco existe en
 *     comentarios → también 404 (RN-004, D-03).
 *   - Éxito → 303 a index.php#respuesta-{id}.
 *   - Fallo interno → 500 genérico (detalle solo en el log, D-06).
 */

declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/validacion.php';
require __DIR__ . '/comentarios.php';
require __DIR__ . '/respuestas.php';
require __DIR__ . '/vistas.php';

// --- 405: solo se acepta POST -------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Método no permitido</title></head>';
    echo '<body><p>Método no permitido. <a href="index.php">Volver</a></p></body></html>';
    exit;
}

// --- Lectura de la entrada (la fecha del cliente se ignora, RN-007) ------
$autor     = (string) ($_POST['autor'] ?? '');
$contenido = (string) ($_POST['contenido'] ?? '');
$padreId   = filter_var($_POST['comentario_padre_id'] ?? '', FILTER_VALIDATE_INT);

// Para re-dibujar la página conservando lo escrito (D-09)
$origen = 'comentario'; // por defecto: el mensaje se muestra junto al form principal
if ($padreId !== false && $padreId > 0) {
    $origen = 'comentario-' . $padreId;
}

// --- Validación en el servidor (orden D-02) ------------------------------
$error = validar_publicacion($autor, $contenido, 'respuesta');

if ($error !== null) {
    http_response_code(422);
    [$comentarios, $agrupadas] = cargar_conversacion(db_conexion());
    dibujar_pagina($comentarios, $agrupadas, $error, $autor, $contenido, $origen);
    exit;
}

// --- Padre existente (RN-006, F-RES-001 CA-6) ----------------------------
if ($padreId === false || $padreId < 1 || !existe_comentario(db_conexion(), $padreId)) {
    http_response_code(404);
    [$comentarios, $agrupadas] = cargar_conversacion(db_conexion());
    dibujar_pagina(
        $comentarios,
        $agrupadas,
        'El comentario que intentas responder no existe',
        $autor,
        $contenido,
        'comentario' // el form del padre ya no existe: el mensaje va al form principal
    );
    exit;
}

// --- Persistencia y 303 (CA-1, CA-7) ------------------------------------
try {
    $id = guardar_respuesta(db_conexion(), $padreId, recortar($autor), recortar($contenido));
} catch (Throwable $e) {
    error_log('[responder.php] ' . $e->getMessage());
    http_response_code(500);
    [$comentarios, $agrupadas] = cargar_conversacion(db_conexion());
    dibujar_pagina($comentarios, $agrupadas, 'Ocurrió un error interno. Tu respuesta no se guardó.', $autor, $contenido, $origen);
    exit;
}

header('Location: index.php#respuesta-' . $id, true, 303);
exit;
