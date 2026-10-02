CREATE DATABASE IF NOT EXISTS app_comentarios
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE app_comentarios;

CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    author VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO comments (author, content, created_at) VALUES
('María', '¡Muy buena la clase de hoy! El concepto de vibe coding me dejó pensando.', NOW()),
('Juan', '¿Alguien pudo hacer funcionar la app después del sprint?', NOW()),
('Ana', 'Creo que el problema principal es que sin spec la IA no sabe qué puede tocar.', NOW());
