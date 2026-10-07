<?php
/**
 * index.php — Página principal (entrada de la presentación)
 * Paso B — listado mínimo (F-COM-002 entra temprano por la dependencia D-D)
 * Refs: D-05, D-06, D-07, D-09 · F-COM-002 CA-1, CA-2, CA-4
 *
 * - Listado: fecha_creacion DESC, id DESC (D-05).
 * - Conversación única global: index.php lista todos los comentarios (D-07).
 * - 500 genérico si falla la consulta (D-06).
 */

declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/comentarios.php';
require __DIR__ . '/respuestas.php'; // módulo de consulta: cargar_conversacion() agrupa respuestas
require __DIR__ . '/vistas.php';

try {
    [$comentarios, $respuestasAgrupadas] = cargar_conversacion(db_conexion());
} catch (Throwable $e) {
    error_log('[index.php] ' . $e->getMessage());
    http_response_code(500);
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Error</title></head>';
    echo '<body><p>Ocurrió un error interno. Intentá de nuevo más tarde.</p></body></html>';
    exit;
}

dibujar_pagina($comentarios, $respuestasAgrupadas);
