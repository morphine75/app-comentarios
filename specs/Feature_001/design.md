# design.md — App de Comentarios

**Alumno:** Martin Dominguez
**Versión del documento:** 0.2
**requirements.md de referencia:** requisitos/requirements.md (v0.4)
**Stack Tecnológico Asignado:** PHP 8.2, MySQL (mysqli) y Vanilla JavaScript (JS nativo)

---

## 1. Arquitectura general

El sistema se organiza en tres capas lógicas bien diferenciadas bajo una arquitectura monolítica tradicional en **PHP**: una capa de presentación que recibe las acciones del lector y muestra los resultados, una capa de aplicación (lógica de negocio) que contiene las reglas de negocio y las validaciones, y una capa de persistencia que guarda y recupera los comentarios y respuestas interactuando con **MySQL**.

La comunicación es siempre síncrona y en una sola dirección: el lector interactúa con la interfaz, esta delega en la capa de aplicación, y esta última consulta o modifica la persistencia. No existe comunicación en tiempo real ni componentes distribuidos. Toda la aplicación gira en torno a **una única conversación global** (D-07).

---

## 2. Decisiones técnicas (ADR)

### D-01 — Persistencia en MySQL con dos tablas relacionadas
- **Contexto:** Se necesita guardar comentarios y respuestas de forma permanente, con relaciones claras (comentarios → respuestas) y consultas ordenadas por fecha.
- **Decisión:** Usar **MySQL** (InnoDB, `utf8mb4`) con **dos tablas relacionadas**, accedidas mediante **mysqli**:

  | Tabla | Columnas |
  |---|---|
  | `comentarios` | `id` INT UNSIGNED AUTO_INCREMENT PK · `autor` VARCHAR(50) NOT NULL · `contenido` VARCHAR(500) NOT NULL · `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP |
  | `respuestas` | `id` INT UNSIGNED AUTO_INCREMENT PK · `comentario_padre_id` INT UNSIGNED NOT NULL, FK → `comentarios.id` · `autor` VARCHAR(50) NOT NULL · `contenido` VARCHAR(500) NOT NULL · `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP |

  Índices: `comentarios(fecha_creacion)` y `respuestas(comentario_padre_id, fecha_creacion)`.
- **Alternativas consideradas:**
  - Archivos JSON o CSV
  - Tabla única autorreferenciada (descartada: permitiría anidar respuestas y contradice D-03)
- **Justificación:** Las relaciones son fijas y de poca profundidad (máximo 1 nivel). Un modelo relacional permite integridad referencial sencilla (la FK garantiza RN-006) y consultas ordenadas de forma natural. Además, es el entorno ya disponible en el laboratorio (**XAMPP**).
- **Requisito relacionado:** RN-003, RN-006, RN-007, F-COM-001, F-COM-002, F-RES-001

### D-02 — Validaciones en el servidor (PHP)
- **Contexto:** Los criterios de aceptación exigen rechazar contenidos inválidos (vacíos, solo espacios, fuera de rango de longitud, autor inválido, padre inexistente).
- **Decisión:** Todas las validaciones de negocio se ejecutan en el servidor mediante **PHP** antes de persistir. El cliente puede hacer validaciones de ayuda con **Vanilla JS** y atributos HTML5, pero no son la fuente de verdad.
  - **Normalización:** autor y contenido se recortan (espacios, tabuladores, saltos de línea) al inicio y al final antes de validar y de guardar. Se usa `preg_replace('/^\s+|\s+$/u', '', $texto)` para cubrir Unicode.
  - **Longitud:** se mide con `mb_strlen($texto, 'UTF-8')`, nunca con `strlen`.
  - **Orden de validación:** autor (RN-005) → contenido vacío (RN-002) → mínimo (RN-008) → máximo (RN-001) → comentario padre existente (RN-006, solo respuestas). Se muestra el primer error encontrado de cada campo.
  - **Mensajes:** los del catálogo de `requirements.md` §7, sin modificaciones.
  - **Padre inexistente:** `SELECT` preparado antes del `INSERT`; si no existe se responde `404` con el mensaje del catálogo y no se guarda nada. Además, la FK actúa como segunda barrera.
  - **Códigos HTTP:** `303` éxito, `422` validación fallida, `404` padre inexistente, `405` método distinto de POST, `500` error interno sin detalles al usuario.
- **Alternativas consideradas:**
  - Solo validación en el navegador
  - Validación duplicada (cliente + servidor) con la misma lógica
- **Justificación:** El cliente puede ser manipulado. La única forma de garantizar RN-001 a RN-008 es validar siempre en el backend.
- **Requisito relacionado:** RN-001, RN-002, RN-005, RN-006, RN-008, F-COM-001 (CA-2 a CA-5), F-RES-001 (CA-2, CA-3, CA-5, CA-6)

### D-03 — Respuestas de un solo nivel (sin anidamiento)
- **Contexto:** El dominio prohíbe responder a una respuesta (RN-004).
- **Decisión:** El modelo de datos y la interfaz solo permiten crear respuestas asociadas a un comentario padre (`comentario_padre_id` → `comentarios.id`). La tabla `respuestas` no tiene ninguna columna que apunte a otra respuesta. La interfaz no muestra el formulario ni el botón "Responder" dentro de una respuesta, y `responder.php` solo acepta ids de la tabla `comentarios`.
- **Alternativas consideradas:**
  - Árbol de comentarios con profundidad ilimitada
  - Profundidad máxima configurable
- **Justificación:** Simplifica enormemente el modelo, las consultas SQL y la interfaz. Cumple exactamente el alcance de v1.
- **Requisito relacionado:** RN-004, F-RES-001 (CA-4)

### D-04 — Autor como texto libre (sin autenticación)
- **Contexto:** No hay usuarios registrados. El autor es solo un nombre visible.
- **Decisión:** El campo `autor` es un string libre que se pide en cada publicación. No se crea ni se gestiona una entidad Usuario en la base de datos.
- **Alternativas consideradas:**
  - Sistema de login mínimo
  - Autor anónimo obligatorio
- **Justificación:** Mantener el alcance mínimo. La autenticación está explícitamente fuera de alcance.
- **Requisito relacionado:** RN-003, RN-005, F-COM-001, F-RES-001

### D-05 — Ordenamiento del listado
- **Contexto:** Los lectores deben ver primero los comentarios más recientes (F-COM-002), pero las respuestas se leen como una conversación, de la más antigua a la más nueva.
- **Decisión:**
  - Comentarios: `ORDER BY fecha_creacion DESC, id DESC`.
  - Respuestas de cada comentario: `ORDER BY fecha_creacion ASC, id ASC`.
  - El `id` actúa como desempate cuando dos filas comparten el mismo segundo.
  - Las respuestas se cargan con una sola consulta para todos los comentarios mostrados y se agrupan en PHP (sin consulta por comentario).
- **Alternativas consideradas:**
  - Orden cronológico ascendente para comentarios
  - Orden por cantidad de respuestas
  - Respuestas también en orden descendente
- **Justificación:** Es el comportamiento más habitual en sistemas de comentarios y coincide con los criterios de aceptación.
- **Requisito relacionado:** F-COM-002 (CA-1, CA-3)

### D-06 — Seguridad de entrada y salida
- **Contexto:** La aplicación recibe texto libre de usuarios anónimos y lo vuelve a mostrar a otros lectores.
- **Decisión:**
  - Toda consulta usa **sentencias preparadas de mysqli** (`prepare`, `bind_param`, `execute`). Nunca se concatenan datos del usuario en el SQL.
  - Toda salida de datos del usuario pasa por `htmlspecialchars($texto, ENT_QUOTES, 'UTF-8')`.
  - La conexión usa `utf8mb4` y `mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)`. Los errores técnicos se registran en el log y el usuario solo ve un mensaje genérico.
  - Los saltos de línea del contenido se muestran con `nl2br` aplicado **después** de `htmlspecialchars`.
- **Alternativas consideradas:**
  - Escapar manualmente con `real_escape_string`
  - Filtrar etiquetas con expresiones regulares
- **Justificación:** Las sentencias preparadas y el escape en la salida son la defensa estándar contra inyección SQL y XSS, y no dependen de listas de caracteres prohibidos.
- **Requisito relacionado:** RN-001, RN-002 (integridad del contenido); restricciones críticas de `AGENTS.md` §1

### D-07 — Conversación única global (sin entidad Artículo)
- **Contexto:** Algunos textos hablaban de "artículo", pero no existe tal entidad en los requisitos ni listado de artículos.
- **Decisión:** La aplicación tiene una única conversación. No existe tabla `articulos` ni columna `articulo_id`; `index.php` lista todos los comentarios.
- **Alternativas consideradas:**
  - Un `articulo_id` fijo en cada comentario
  - Entidad Artículo con gestión completa
- **Justificación:** Es lo que describe `requirements.md` (alcance mínimo) y evita complejidad que la v1 no necesita. Si en el futuro hay varios artículos, se agrega `articulo_id` en una migración.
- **Requisito relacionado:** `requirements.md` §1 y §6, F-COM-002

### D-08 — Generación, zona horaria y formato de la fecha
- **Contexto:** La fecha debe ser consistente entre PHP y MySQL y no debe depender del cliente (RN-007).
- **Decisión:**
  - La fecha la genera **MySQL** con `DEFAULT CURRENT_TIMESTAMP`. Ningún `INSERT` incluye `fecha_creacion`.
  - Zona horaria única `America/Argentina/Buenos_Aires` (UTC-3): `date_default_timezone_set(...)` en PHP y `SET time_zone = '-03:00'` al abrir la conexión.
  - Formato de visualización: `d/m/Y H:i` (por ejemplo, `14/05/2026 09:30`), aplicado con `DateTime` en PHP.
- **Alternativas consideradas:**
  - Generar la fecha en PHP con `date()`
  - Mostrar solo la fecha
  - Guardar en UTC y convertir al mostrar
- **Justificación:** Una sola fuente de tiempo evita desfasajes, y el formato cumple F-COM-002 CA-4. La zona UTC-3 corresponde al entorno de laboratorio.
- **Requisito relacionado:** RN-003, RN-007, F-COM-001 (CA-6), F-COM-002 (CA-4), F-RES-001 (CA-7)

### D-09 — Flujo de formularios: POST → Redirect → GET
- **Contexto:** Un refresco tras enviar un formulario puede duplicar el comentario, y no hay sesiones para guardar mensajes.
- **Decisión:**
  - Formularios HTML estándar con `method="post"`. Sin `fetch` en v1.
  - **Éxito:** respuesta `303` hacia `index.php#comentario-{id}`. No hay mensaje de éxito: el elemento nuevo aparecido en el listado es la confirmación (F-COM-001 CA-1).
  - **Error:** el mismo handler vuelve a dibujar la página con código `422` (o `404`), muestra el mensaje junto al formulario y conserva lo que el usuario había escrito. No se usan sesiones.
  - **Duplicados:** el patrón PRG evita el reenvío al refrescar; opcionalmente `app.js` deshabilita el botón al enviar.
  - La aplicación debe seguir funcionando con JavaScript desactivado.
- **Alternativas consideradas:**
  - Envío con `fetch` y actualización parcial del DOM
  - Mensajes flash con sesiones
  - Token anti-duplicados
- **Justificación:** Es lo más simple que cumple los requisitos y respeta que no hay sesiones (§4).
- **Requisito relacionado:** F-COM-001 (CA-1 a CA-5), F-RES-001 (CA-1 a CA-6)

---

## 3. Estructura de módulos

- **Módulo de presentación (HTML + CSS + Vanilla JS)** — `index.php`, `vistas.php`, `assets/`
  Muestra el listado de comentarios + respuestas y los formularios de creación. Recibe las acciones del lector y muestra los mensajes de error. El JavaScript es solo una mejora (contador de caracteres, botón deshabilitado).

- **Módulo de comentarios (PHP)** — `comentar.php`, `comentarios.php`
  Recibe la solicitud de creación de un comentario, valida autor y contenido según las reglas de negocio y lo persiste en la conversación única.

- **Módulo de respuestas (PHP)** — `responder.php`, `respuestas.php`
  Recibe la solicitud de creación de una respuesta, verifica que el comentario padre exista, valida autor y contenido, y la persiste asociada al comentario padre.

- **Módulo de validación (PHP)** — `validacion.php`
  Concentra las reglas RN-001, RN-002, RN-005 y RN-008 y los mensajes del catálogo. Lo usan los módulos de comentarios y de respuestas.

- **Módulo de consulta (PHP + SQL)**
  Obtiene todos los comentarios ordenados del más reciente al más antiguo, junto con sus respuestas asociadas (D-05), mediante consultas a MySQL.

- **Módulo de persistencia (PHP - mysqli)** — `db.php`
  Encapsula el acceso a la base de datos MySQL (conexión, zona horaria, altas y consultas preparadas). No contiene reglas de negocio.

---

## 4. Fuera de alcance técnico

- **Comunicación en tiempo real (WebSockets / Server-Sent Events)** — se descartó porque no hay requisito de actualización automática; un refresco de página es suficiente.
- **Framework frontend moderno (React, Vue, etc.)** — se descartó para mantener el enfoque en la lógica de servidor y las reglas de negocio con JavaScript nativo.
- **Sistema de autenticación o sesiones de usuario** — se descartó porque está explícitamente fuera del alcance funcional.
- **Caché o sistema de colas** — se descartó por la baja concurrencia esperada en el entorno local (XAMPP).
- **Entidad Artículo y múltiples conversaciones** — se descartó en D-07.
- **Uso de Composer o librerías externas** — se descartó para mantener el entorno XAMPP simple.

---

## 5. Trazabilidad

### Reglas de negocio

| Regla | Criterios de aceptación | Decisiones | Módulo |
|---|---|---|---|
| RN-001 | F-COM-001 CA-2 · F-RES-001 CA-2 | D-01, D-02 | Validación |
| RN-002 | F-COM-001 CA-3 · F-RES-001 CA-2 | D-02 | Validación |
| RN-003 | F-COM-001 CA-1 · F-RES-001 CA-1 | D-01, D-04, D-08 | Comentarios, Respuestas |
| RN-004 | F-RES-001 CA-4 | D-03 | Respuestas, Presentación |
| RN-005 | F-COM-001 CA-4 · F-RES-001 CA-3 | D-02, D-04 | Validación |
| RN-006 | F-RES-001 CA-6 | D-01, D-02 | Respuestas |
| RN-007 | F-COM-001 CA-6 · F-RES-001 CA-7 · F-COM-002 CA-4 | D-01, D-08 | Persistencia |
| RN-008 | F-COM-001 CA-5 · F-RES-001 CA-5 | D-02 | Validación |

### Features

| Feature | Reglas | Decisiones | Módulos |
|---|---|---|---|
| F-COM-001 Crear comentario | RN-001, 002, 003, 005, 007, 008 | D-01, D-02, D-04, D-06, D-08, D-09 | Presentación, Comentarios, Validación, Persistencia |
| F-COM-002 Listar comentarios | RN-007 | D-01, D-05, D-07, D-08 | Presentación, Consulta |
| F-RES-001 Responder | RN-001 a RN-008 | D-01, D-02, D-03, D-04, D-06, D-08, D-09 | Presentación, Respuestas, Validación, Persistencia |

### Decisiones

| Decisión | Agente principal (ver `AGENTS.md`) |
|---|---|
| D-01, D-05, D-07 (estructura) | Agente 1 (Base de datos) |
| D-02, D-06, D-08, D-09 | Agente 2 (Backend) |
| D-03, D-05 (visualización), D-08 (formato), D-09 | Agente 3 (Frontend) |

---

## 6. Dudas técnicas — estado

Todas las dudas anteriores quedaron resueltas:

| Duda | Resolución |
|---|---|
| ¿Mensaje de éxito tras crear un comentario o respuesta? | No. Basta con que aparezca en el listado tras el redirect (D-09). |
| ¿Formato de fecha? | `d/m/Y H:i`, generada por MySQL, zona UTC-3 (D-08). |
| ¿Protección contra envíos duplicados? | Sí: patrón POST → Redirect → GET y, opcionalmente, botón deshabilitado con JS (D-09). |
| (de requirements §8) ¿Cómo se identifica el artículo? | No hay artículo; conversación única (D-07). |
| (de requirements §8) ¿Comentario padre inexistente? | Se rechaza con `404` y mensaje (D-02). |
| (de requirements §8) ¿Dónde se valida? | En el servidor; el cliente solo ayuda (D-02). |

Si durante el desarrollo aparece una duda nueva, se agrega a esta sección como **pendiente** y el agente debe detenerse a consultar (ver `AGENTS.md` §4).
</document_content>