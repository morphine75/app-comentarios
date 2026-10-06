-- =============================================================
-- App de Comentarios — Paso A (Agente 1: Database Agent)
-- Especificación: specs/Feature_001/requirements.md (v0.4)
-- Arquitectura: specs/Feature_001/design.md (v0.2) — D-01, D-05, D-07
-- Listo para importar en phpMyAdmin (XAMPP)
-- =============================================================

-- Base de datos única de la conversación global (D-07)
CREATE DATABASE IF NOT EXISTS app_comentarios
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE app_comentarios;

-- -------------------------------------------------------------
-- Tabla: comentarios (F-COM-001 — Crear un comentario)
--   autor          -> RN-005 (2 a 50 caracteres, validado en PHP)
--   contenido      -> RN-001 / RN-008 (3 a 500 caracteres, validado en PHP)
--   fecha_creacion -> RN-007: la genera MySQL (DEFAULT CURRENT_TIMESTAMP),
--                     ningún INSERT incluye esta columna (D-08)
--   Sin columna articulo_id: conversación única global (D-07)
-- -------------------------------------------------------------
CREATE TABLE comentarios (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  autor          VARCHAR(50)  NOT NULL,
  contenido      VARCHAR(500) NOT NULL,
  fecha_creacion DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_comentarios_fecha_creacion (fecha_creacion)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
