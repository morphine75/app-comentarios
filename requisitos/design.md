# design.md — App de Comentarios

**Alumno:** Martin Dominguez
**requirements.md de referencia:** requisitos/requirements.md
**Stack Tecnológico Asignado:** PHP 8.2, MySQL y Vanilla JavaScript (JS nativo)

---

## 1. Arquitectura general

El sistema se organiza en tres capas lógicas bien diferenciadas bajo una arquitectura monolítica tradicional en **PHP**: una capa de presentación que recibe las acciones del lector y muestra los resultados, una capa de aplicación (lógica de negocio) que contiene las reglas de negocio y las validaciones, y una capa de persistencia que guarda y recupera los comentarios y respuestas interactuando con **MySQL**.  

La comunicación es siempre síncrona y en una sola dirección: el lector interactúa con la interfaz, esta delega en la capa de aplicación, y esta última consulta o modifica la persistencia. No existe comunicación en tiempo real ni componentes distribuidos.

---

## 2. Decisiones técnicas (ADR)

### D-01 — Persistencia en base de datos relacional (MySQL)
- **Contexto:** Se necesita guardar comentarios y respuestas de forma permanente, con relaciones claras (comentarios → respuestas) y consultas ordenadas por fecha.
- **Decisión:** Usar **MySQL** como motor de base de datos relacional con tablas unificadas o relacionadas para Comentario y Respuesta, gestionando la conexión mediante **mysqli** en PHP.
- **Alternativas consideradas:**  
  - Archivos JSON o CSV  
- **Justificación:** Las relaciones son fijas y de poca profundidad (máximo 1 nivel). Un modelo relacional permite integridad referencial sencilla y consultas ordenadas de forma natural. Además, es el entorno ya disponible en el laboratorio (**XAMPP**).
- **Requisito relacionado:** RN-006, RN-007, F-COM-002, F-ART-001

### D-02 — Validaciones en el servidor (PHP)
- **Contexto:** Los criterios de aceptación exigen rechazar contenidos inválidos (vacíos, solo espacios, fuera de rango de longitud, autor inválido).
- **Decisión:** Todas las validaciones de negocio se ejecutan en el servidor mediante **PHP** antes de persistir. El cliente puede hacer validaciones de ayuda con **Vanilla JS**, pero no son la fuente de verdad.
- **Alternativas consideradas:**  
  - Solo validación en el navegador  
  - Validación duplicada (cliente + servidor) con la misma lógica
- **Justificación:** El cliente puede ser manipulado. La única forma de garantizar RN-001 a RN-008 es validar siempre en el backend.
- **Requisito relacionado:** RN-001, RN-002, RN-005, F-COM-001 (CA-2, CA-3, CA-4), F-RES-001

### D-03 — Respuestas de un solo nivel (sin anidamiento)
- **Contexto:** El dominio prohíbe responder a una respuesta (RN-004).
- **Decisión:** El modelo de datos en **MySQL** y la interfaz solo permiten crear respuestas asociadas a un comentario padre (`comentario_padre_id`). No existe la posibilidad de indicar un "padre de respuesta".
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

### D-05 — Ordenamiento por fecha de creación descendente
- **Contexto:** Los lectores deben ver primero los comentarios más recientes (F-COM-002).
- **Decisión:** Toda consulta de listado ordena por `fechaCreacion` de forma descendente (`ORDER BY fecha_creacion DESC` en la consulta SQL).
- **Alternativas consideradas:**  
  - Orden cronológico ascendente  
  - Orden por cantidad de respuestas
- **Justificación:** Es el comportamiento más habitual en sistemas de comentarios y coincide con el criterio de aceptación.
- **Requisito relacionado:** F-COM-002 (CA-1)

---

## 3. Estructura de módulos

- **Módulo de presentación (HTML + CSS + Vanilla JS)**  
  Muestra el listado de comentarios + respuestas y los formularios de creación. Recibe las acciones del lector y muestra los mensajes de error o éxito. Puede usar JavaScript nativo para mejorar la experiencia de envío (ej. manejo de formularios o alertas).

- **Módulo de comentarios (PHP)**  
  Recibe la solicitud de creación de un comentario, valida autor y contenido según las reglas de negocio, asocia el comentario al artículo y lo persiste.

- **Módulo de respuestas (PHP)**  
  Recibe la solicitud de creación de una respuesta, verifica que el comentario padre exista, valida autor y contenido, y la persiste asociada al comentario padre.

- **Módulo de consulta (PHP + SQL)**  
  Obtiene todos los comentarios de un artículo ordenados del más reciente al más antiguo, junto con sus respuestas asociadas mediante consultas optimizadas a MySQL.

- **Módulo de persistencia (PHP - mysqli)**  
  Encapsula el acceso a la base de datos MySQL (altas y consultas). No contiene reglas de negocio.

---

## 4. Fuera de alcance técnico

- **Comunicación en tiempo real (WebSockets / Server-Sent Events)** — se descartó porque no hay requisito de actualización automática; un refresco de página es suficiente.
- **Framework frontend moderno (React, Vue, etc.)** — se descartó para mantener el enfoque en la lógica de servidor y las reglas de negocio con JavaScript nativo.
- **Sistema de autenticación o sesiones de usuario** — se descartó porque está explícitamente fuera del alcance funcional.
- **Caché o sistema de colas** — se descartó por la baja concurrencia esperada en el entorno local (XAMPP).

---

## 5. Dudas técnicas pendientes

- ¿Se debe mostrar un mensaje de éxito después de crear un comentario/respuesta, o basta con que aparezca en el listado tras la recarga de página?
- ¿Qué formato exacto se usará para mostrar la fecha de creación en PHP (solo fecha, fecha + hora)?
- ¿Se contemplará algún mecanismo simple de protección contra envíos duplicados (ej: deshabilitar el botón con JS al hacer submit)?
