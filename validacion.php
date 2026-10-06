<?php
/**
 * validacion.php — Módulo de validación (reglas de negocio)
 * Paso B (Agente 2: Backend Dev Agent) — D-02 · RN-001, RN-002, RN-005, RN-008
 *
 * El servidor es la única fuente de verdad (D-02):
 * - Recorte Unicode de extremos antes de validar y de guardar.
 * - Longitud medida con mb_strlen(..., 'UTF-8'), nunca con strlen.
 * - Orden: autor → contenido vacío → mínimo → máximo.
 *   (→ padre existente, RN-006: se valida en el módulo de respuestas).
 * - Mensajes exactos de requirements.md §7, sin modificaciones.
 */

declare(strict_types=1);

const AUTOR_MIN = 2;
const AUTOR_MAX = 50;
const CONTENIDO_MIN = 3;
const CONTENIDO_MAX = 500;

/**
 * Recorta espacios, tabuladores y saltos de línea de los extremos (Unicode).
 */
function recortar(string $texto): string
{
    return preg_replace('/^\s+|\s+$/u', '', $texto) ?? '';
}

/**
 * Valida una publicación (comentario o respuesta).
 *
 * @param string $autor     Valor crudo tal como lo envió el cliente.
 * @param string $contenido Valor crudo tal como lo envió el cliente.
 * @param string $tipo      'comentario' | 'respuesta' — define el mensaje (§7).
 * @return string|null Primer error encontrado, o null si es válido.
 */
function validar_publicacion(string $autor, string $contenido, string $tipo): ?string
{
    $esComentario = $tipo === 'comentario';

    // 1) Autor — RN-005 (2 a 50 caracteres tras recortar)
    $longAutor = mb_strlen(recortar($autor), 'UTF-8');
    if ($longAutor < AUTOR_MIN || $longAutor > AUTOR_MAX) {
        return 'El nombre del autor debe tener entre 2 y 50 caracteres';
    }

    // 2) Contenido vacío o solo espacios — RN-002
    $longContenido = mb_strlen(recortar($contenido), 'UTF-8');
    if ($longContenido === 0) {
        return $esComentario ? 'El comentario no puede estar vacío' : 'La respuesta no puede estar vacía';
    }

    // 3) Mínimo — RN-008 (al menos 3 caracteres)
    if ($longContenido < CONTENIDO_MIN) {
        return $esComentario ? 'El comentario debe tener al menos 3 caracteres' : 'La respuesta debe tener al menos 3 caracteres';
    }

    // 4) Máximo — RN-001 (nunca más de 500 caracteres)
    if ($longContenido > CONTENIDO_MAX) {
        return $esComentario ? 'El comentario no puede superar los 500 caracteres' : 'La respuesta no puede superar los 500 caracteres';
    }

    return null;
}
