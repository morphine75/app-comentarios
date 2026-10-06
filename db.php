<?php
/**
 * db.php — Módulo de persistencia (conexión mysqli)
 * Paso B (Agente 2: Backend Dev Agent) — D-06, D-08
 *
 * - mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT) → las fallas
 *   lanzan excepción; el detalle va al log, el usuario ve un 500 genérico (D-06).
 * - Zona horaria única UTC-3 en PHP y MySQL (D-08): una sola fuente de tiempo.
 * - utf8mb4 en la conexión para soportar cualquier carácter (D-06).
 */

declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'app_comentarios';
const DB_USER = 'root';
const DB_PASS = '';

/**
 * Devuelve la conexión (singleton por request).
 */
function db_conexion(): mysqli
{
    static $conexion = null;

    if ($conexion instanceof mysqli) {
        return $conexion;
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    date_default_timezone_set('America/Argentina/Buenos_Aires');

    $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    $conexion->set_charset('utf8mb4');
    $conexion->query("SET time_zone = '-03:00'");

    return $conexion;
}
