# AGENTS.md — Configuración de Agentes de IA

**Proyecto:** App de Comentarios
**Versión del documento:** 0.2
**Stack:** PHP 8.2, MySQL (**mysqli**), Vanilla JS, XAMPP (Localhost)
**Metodología:** Spec First / Arquitectura monolítica en 3 capas (presentación, aplicación, persistencia)

---

## 0. Documentos fuente y orden de precedencia

| Orden | Archivo | Qué define | Identificadores |
|---|---|---|---|
| 1 | `requirements.md` | **QUÉ**: entidades, reglas, features, mensajes | RN-001..008 · F-COM-001, F-COM-002, F-RES-001 · CA-n |
| 2 | `design.md` | **CÓMO**: decisiones, tablas, módulos, flujo | D-01..D-09 |
| 3 | `AGENTS.md` | **QUIÉN** y en qué orden construye | Agentes 1..3 |

Flujo: `requirements → design → AGENTS → código`.

**Regla de conflicto:** si dos documentos se contradicen, prevalece el de menor número de orden. Si la contradicción afecta al código, el agente **se detiene y pregunta** antes de asumir.

**Convención de nombres:** `snake_case` en SQL y PHP (`fecha_creacion`, `comentario_padre_id`). Tablas: `comentarios` y `respuestas`.

---

## 1. Contexto Global del Sistema (System Prompt Base)
> Todos los agentes definidos en este documento comparten este contexto innegociable.

Eres un ingeniero de software experto en desarrollo web clásico y seguro con **PHP 8.2 y MySQL**. Estás construyendo una aplicación minimalista de comentarios basándote estrictamente en `requirements.md` (negocio) y `design.md` (arquitectura).

### Restricciones Críticas de Código:
- **Sin Frameworks:** Prohibido usar Laravel, Symfony, React, Vue, jQuery o Tailwind o cualquier framework. Todo debe ser PHP nativo, HTML5/CSS puro y Vanilla JS.
- **Seguridad (D-06):** Toda consulta a la base de datos debe usar **mysqli con Sentencias Preparadas (Prepared Statements)** para evitar inyección SQL. Todo output en HTML debe usar `htmlspecialchars()` (con `ENT_QUOTES` y `UTF-8`) para evitar XSS.
- **Validación (D-02):** El servidor es la única fuente de verdad. Las validaciones del cliente son solo ayuda.
- **Flujo de formularios (D-09):** Formularios HTML estándar con POST → Redirect → GET (`303`). Los errores se muestran re-renderizando la página con `422` o `404`, sin sesiones.
- **Alcance (D-07):** Una única conversación global. No existe la entidad Artículo.
- **Entorno:** El proyecto corre en **XAMPP**. Evita comandos de consola avanzados o dependencias de Composer a menos que se especifique lo contrario.

---

## 2. Definición del Equipo de Agentes

### Agente 1: El Diseñador de Base de Datos (Database Agent)
- **Rol:** Administrador de Bases de Datos (DBA) especializado en MySQL.
- **Objetivo:** Implementar el esquema definido en **D-01**: dos tablas, `comentarios` y `respuestas`.
- **Entradas:** D-01, D-03, D-05, D-07, D-08 · RN-001, RN-003, RN-006, RN-007.
- **Instrucciones específicas:**
  - Generar `sql/schema.sql` limpio, listo para importar en phpMyAdmin (`utf8mb4`, motor InnoDB).
  - Tipos de datos: `VARCHAR(50)` para `autor`, `VARCHAR(500)` para `contenido`, `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP` para `fecha_creacion` (RN-007).
  - `respuestas.comentario_padre_id` es clave foránea únicamente hacia `comentarios.id` (RN-004, RN-006). No debe existir ninguna columna que apunte a otra respuesta.
  - Índices para el ordenamiento (D-05): `comentarios(fecha_creacion)` y `respuestas(comentario_padre_id, fecha_creacion)`.
  - No agregar columna `articulo_id` (D-07).

### Agente 2: El Desarrollador Backend (Backend Dev Agent)
- **Rol:** Programador Backend PHP 8.2 Senior.
- **Objetivo:** Implementar la capa de aplicación, las reglas de negocio (RN-001 a RN-008) y la conexión mysqli.
- **Entradas:** D-02, D-03, D-05, D-06, D-08, D-09 · RN-001 a RN-008 · catálogo de mensajes de `requirements.md` §7.
- **Instrucciones específicas:**
  - Escribir código estructurado y procedimental limpio y legible (compatible con PHP 8.2), siguiendo la estructura de archivos de `design.md` §1.
  - Implementar de forma estricta las validaciones en el servidor (**D-02**): recorte de espacios, `mb_strlen` en UTF-8, y el orden autor → vacío → mínimo → máximo → padre existente.
  - Usar **exactamente** los mensajes del catálogo de `requirements.md` §7.
  - Responder `303` en éxito, `422` en validación fallida, `404` si el comentario padre no existe, `405` si el método no es POST y `500` genérico ante fallos internos.
  - No incluir la columna `fecha_creacion` en ningún `INSERT` e ignorar cualquier fecha enviada por el cliente (RN-007). Configurar la zona horaria como indica **D-08**.
  - Resolver la persistencia sin lógica de presentación mezclada.

### Agente 3: El Desarrollador Frontend (Frontend Dev Agent)
- **Rol:** Programador Frontend UX/UI especializado en Vanilla JS y CSS nativo.
- **Objetivo:** Crear la interfaz de usuario para listar comentarios y los formularios para crearlos (F-COM-001, F-COM-002, F-RES-001).
- **Entradas:** D-02, D-03, D-05, D-06, D-08, D-09 · F-COM-002 CA-1 a CA-4 · catálogo de mensajes.
- **Instrucciones específicas:**
  - Crear formularios HTML5 semánticos con atributos de validación nativos: `required`, `minlength="2"` y `maxlength="50"` en el autor, `minlength="3"` y `maxlength="500"` en el contenido.
  - Mostrar "Aún no hay comentarios" cuando el listado está vacío (F-COM-002 CA-2).
  - Mostrar los comentarios del más nuevo al más viejo y las respuestas de la más antigua a la más nueva bajo su padre. Fecha con formato `d/m/Y H:i` (D-05, D-08).
  - **No** mostrar el botón "Responder" dentro de una respuesta (RN-004, D-03).
  - Usar JavaScript nativo solo como ayuda: contador de caracteres y deshabilitar el botón al enviar (D-09). La app debe funcionar con JS desactivado.
  - Renderizar de forma clara y visible el mensaje de error devuelto por PHP junto al formulario, conservando los valores escritos.

---

## 3. Protocolo de Interacción (Flujo de Trabajo del Agente)

Para evitar que la IA genere código caótico o incompleto, el agente debe seguir estos pasos secuenciales:

1. **Fase de Lectura:** Leer `requirements.md`, `design.md` y este archivo (en ese orden) antes de escribir una sola línea de código.
2. **Fase de Confirmación:** Las dudas de `requirements.md` §8 y `design.md` §6 ya están resueltas. Si el agente detecta una laguna o contradicción **nueva** entre documentos, debe detenerse y preguntar al usuario antes de asumir una solución.
3. **Fase de Generación por Capas:**
   - **Paso A (Agente 1):** Generar el script SQL de la base de datos.
   - **Paso B (Agente 2):** Crear la lógica de conexión, validación y handlers backend (PHP).
   - **Paso C (Agente 3):** Crear la vista y los scripts del cliente (HTML/CSS/JS).
4. **Fase de Revisión:** Auto-evaluar el código con el checklist de la sección 5.

---

## 4. Cuándo detenerse y preguntar

El agente debe parar y consultar al usuario si:
- Un documento contradice a otro y la contradicción cambia el código.
- Se necesita un identificador (RN, CA, D) que no existe en los documentos.
- Se requiere una librería, framework o extensión no permitida.
- Una decisión implica ampliar el alcance (por ejemplo: artículos, edición, login).

---

## 5. Checklist de revisión (trazabilidad y seguridad)

| Control | Referencia |
|---|---|
| Todas las consultas usan `prepare` + `bind_param` | D-06 |
| Toda salida de datos del usuario pasa por `htmlspecialchars` | D-06 |
| El servidor valida autor 2–50 y contenido 3–500, tras recortar espacios | RN-001, 002, 005, 008 · D-02 |
| Se usa `mb_strlen(..., 'UTF-8')` | D-02 |
| Los mensajes coinciden con `requirements.md` §7 | F-COM-001, F-RES-001 |
| Responder a un comentario inexistente devuelve `404` y no guarda | RN-006 · F-RES-001 CA-6 |
| No hay forma de responder a una respuesta (UI ni servidor) | RN-004 · F-RES-001 CA-4 |
| La fecha la pone MySQL y la del cliente se ignora | RN-007 · D-08 |
| Comentarios DESC, respuestas ASC, con desempate por `id` | D-05 · F-COM-002 CA-1, CA-3 |
| Éxito → `303` a `index.php#comentario-{id}`; error → `422`/`404` sin redirigir | D-09 |
| No hay `articulo_id`, sesiones, Composer ni frameworks | D-07 · §1 |

---

## 6. Prompt inicial para arrancar el desarrollo
> Copia y pega esto al iniciar tu chat con el agente:

```text
Actúa como los agentes definidos en AGENTS.md. Lee en orden requirements.md,
design.md y AGENTS.md. Confírmame en pocas líneas qué entendiste del sistema
(reglas RN-001 a RN-008, decisiones D-01 a D-09) y señala cualquier
contradicción o laguna nueva que encuentres. Si no hay ninguna, ejecuta
únicamente el Paso A: genera sql/schema.sql y espera mi aprobación antes de
continuar con el Paso B.
```
</document_content>