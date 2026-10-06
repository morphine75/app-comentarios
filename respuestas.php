<?php
/**
 * respuestas.php — Módulo de respuestas (persistencia y consulta)
 * F-RES-001 (Agente 2: Backend Dev Agent) — D-01, D-03, D-05, D-06, RN-004, RN-006, RN-007
 *
 * - Sentencias preparadas en todas las consultas (D-06).
 * - comentario_padre_id apunta SOLO a comentarios.id: verificar_comentario()
 *   valida el padre antes del INSERT (RN-006) y la FK es la segunda barrera (D-01).
 *   Un id de respuesta no existe en comentarios → 404 (RN-004, D-03).
 * - Ningún INSERT incluye fecha_creacion (RN-007, D-08).
 * - Listado: una sola consulta para todas las respuestas, agrupadas en PHP (D-05).
 */

declare(strict_types=1);

/**
 * ¿Existe el comentario padre? (RN-006 — se consulta antes de insertar, D-02)
 */
function existe_comentario(mysqli $db, int $comentarioPadreId): bool
{
    $stmt = $db->prepare('SELECT 1 FROM comentarios WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $comentarioPadreId);
    $stmt->execute();
    $existe = $stmt->get_result()->fetch_row() !== null;
    $stmt->close();

    return $existe;
}

/**
 * Inserta una respuesta ya validada y recortada. Devuelve su id.
 * La fecha la genera MySQL: la columna no se menciona (RN-007).
 */
function guardar_respuesta(mysqli $db, int $comentarioPadreId, string $autor, string $contenido): int
{
    $stmt = $db->prepare(
        'INSERT INTO respuestas (comentario_padre_id, autor, contenido) VALUES (?, ?, ?)'
    );
    $stmt->bind_param('iss', $comentarioPadreId, $autor, $contenido);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();

    return $id;
}

/**
 * Todas las respuestas de la conversación, de la más antigua a la más nueva
 * con desempate por id (D-05). Una sola consulta para todos los comentarios.
 */
function obtener_respuestas(mysqli $db): array
{
    $stmt = $db->prepare(
        'SELECT id, comentario_padre_id, autor, contenido, fecha_creacion
           FROM respuestas
          ORDER BY fecha_creacion ASC, id ASC'
    );
    $stmt->execute();
    $respuestas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $respuestas;
}

/**
 * Agrupa la lista plana por comentario_padre_id => lista ASC (D-05).
 */
function agrupar_respuestas(array $respuestas): array
{
    $agrupadas = [];
    foreach ($respuestas as $respuesta) {
        $agrupadas[(int) $respuesta['comentario_padre_id']][] = $respuesta;
    }

    return $agrupadas;
}
