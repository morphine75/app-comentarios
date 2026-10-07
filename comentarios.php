<?php
/**
 * comentarios.php — Módulo de comentarios (persistencia y consulta)
 * Paso B (Agente 2: Backend Dev Agent) — RN-007, D-05, D-06, D-09
 *
 * - Sentencias preparadas en todas las consultas (D-06).
 * - Ningún INSERT incluye fecha_creacion: la genera MySQL (RN-007, D-08).
 * - Consulta de listado: fecha_creacion DESC, id DESC (D-05, F-COM-002 CA-1).
 */

declare(strict_types=1);

/**
 * Inserta un comentario ya validado y recortado.
 * Devuelve el id generado (para el redirect 303 a #comentario-{id}).
 *
 * NOTA: la columna fecha_creacion NO se menciona (RN-007) y cualquier
 * fecha enviada por el cliente se ignora por completo.
 */
function guardar_comentario(mysqli $db, string $autor, string $contenido): int
{
    $stmt = $db->prepare('INSERT INTO comentarios (autor, contenido) VALUES (?, ?)');
    $stmt->bind_param('ss', $autor, $contenido);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();

    return $id;
}

/**
 * Devuelve todos los comentarios ordenados del más nuevo al más viejo,
 * con desempate por id cuando comparten segundo (D-05).
 * Necesario para el re-render de error 422 (D-09).
 */
function obtener_comentarios(mysqli $db): array
{
    // Sentencia preparada sin datos dinámicos, para cumplir literalmente
    // con el checklist AGENTS.md §5 / D-06.
    $stmt = $db->prepare(
        'SELECT id, autor, contenido, fecha_creacion
           FROM comentarios
          ORDER BY fecha_creacion DESC, id DESC'
    );
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $resultado;
}

/**
 * Módulo de consulta (design.md §3): la conversación completa para dibujar
 * la página — comentarios DESC + respuestas agrupadas por padre y orden ASC
 * (D-05, una sola consulta de respuestas agrupada en PHP).
 *
 * Devuelve [comentarios, respuestas_agrupadas].
 * Requiere respuestas.php incluido.
 */
function cargar_conversacion(mysqli $db): array
{
    return [
        obtener_comentarios($db),
        agrupar_respuestas(obtener_respuestas($db)),
    ];
}
