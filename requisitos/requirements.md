# requirements.md — App de Comentarios

- **Nombre del proyecto:** App de Comentarios
- **Versión del documento:** 0.4
- **Estado:** LISTO PARA REVISIÓN
- **Semana:** 3 — SDD I (Spec First)
- **Stack Tecnológico Obligatorio:** PHP 8.2, MySQL y Vanilla JavaScript (JS nativo, sin frameworks)

> **Nota:** este es el documento de especificación (el QUÉ).
> NO escribas aquí código, arquitectura compleja ni decisiones de infraestructura detalladas (eso corresponde a `design.md`). Las tecnologías listadas arriba actúan estrictamente como una restricción de entorno para el proyecto.

> **Convención de nombres:** los atributos de las entidades se escriben en `snake_case` y son los mismos nombres que se usan en `design.md` y en la base de datos.

---

## 1. Contexto y objetivos

Aplicación web que permite a los usuarios dejar comentarios y respuestas sobre los mismos comentarios.

Está pensada para un uso general de escala mínima que replica el caso de uso real de comentarios en medios digitales, por ejemplo. Los usuarios son lectores anónimos que solo deben indicar un nombre visible. El sistema existe para facilitar una conversación pública, simple y controlada.

**Alcance de la conversación:** la aplicación tiene **una única conversación global**. No existen artículos, hilos ni secciones: todos los comentarios se muestran en la misma página.

---

## 2. Glosario

- **Comentario:** aporte de texto publicado por un lector.
- **Respuesta:** aporte de texto vinculado a un comentario padre (máximo 1 nivel de profundidad).
- **Autor:** nombre libre que el lector escribe al publicar (no es un usuario registrado del sistema).
- **Conversación:** el conjunto de todos los comentarios y respuestas de la aplicación (única, global).
- **Contenido válido:** texto que, tras eliminar los espacios en blanco al inicio y al final, tiene entre 3 y 500 caracteres.
- **Moderador:** rol que podría aprobar, editar o eliminar comentarios. **Fuera de alcance en v1**.

---

## 3. Entidades del dominio

### Comentario
- `id` — identificador único
- `autor` — nombre visible (2 a 50 caracteres)
- `contenido` — texto de 3 a 500 caracteres
- `fecha_creacion` — fecha y hora de creación (generada por el sistema)

### Respuesta
- `id` — identificador único
- `comentario_padre_id` — identificador del comentario al que responde
- `autor` — nombre visible (2 a 50 caracteres)
- `contenido` — texto de 3 a 500 caracteres
- `fecha_creacion` — fecha y hora de creación (generada por el sistema)

### Relaciones
- Un Comentario puede tener cero o muchas Respuestas.
- Una Respuesta pertenece a exactamente un Comentario.
- Las Respuestas solo tienen 1 nivel de profundidad (no se puede responder a una respuesta).

---

## 4. Reglas de negocio (RN-xxx)

Los límites de longitud se miden sobre el texto **ya sin espacios al inicio y al final**.

- **RN-001** El contenido de un comentario o respuesta nunca supera los 500 caracteres.
- **RN-002** No se guardan comentarios ni respuestas vacíos o que sean solo espacios en blanco.
- **RN-003** Todo comentario y respuesta registra obligatoriamente el nombre del autor y su fecha de creación.
- **RN-004** Las respuestas solo pueden tener un nivel de profundidad (no se responde a una respuesta).
- **RN-005** El nombre del autor es obligatorio y debe tener entre 2 y 50 caracteres.
- **RN-006** Una respuesta solo puede crearse sobre un comentario que exista.
- **RN-007** La fecha de creación se genera automáticamente por el sistema; el usuario no la ingresa.
- **RN-008** El contenido de un comentario o respuesta debe tener al menos 3 caracteres.

### Verificación de cada regla

| Regla | Se verifica en |
|---|---|
| RN-001 | F-COM-001 CA-2 · F-RES-001 CA-2 |
| RN-002 | F-COM-001 CA-3 · F-RES-001 CA-2 |
| RN-003 | F-COM-001 CA-1 · F-RES-001 CA-1 |
| RN-004 | F-RES-001 CA-4 |
| RN-005 | F-COM-001 CA-4 · F-RES-001 CA-3 |
| RN-006 | F-RES-001 CA-6 |
| RN-007 | F-COM-001 CA-6 · F-RES-001 CA-7 · F-COM-002 CA-4 |
| RN-008 | F-COM-001 CA-5 · F-RES-001 CA-5 |

---

## 5. Features (F-xxx-000)

### F-COM-001: Crear un comentario

**Historia de usuario:** Como lector, quiero escribir un comentario, para compartir mi opinión.

**Criterios de aceptación:**

- **CA-1.** Dado que el contenido tiene entre 3 y 500 caracteres y el autor tiene entre 2 y 50 caracteres, cuando envío el formulario, entonces el comentario se guarda y se muestra en el listado con autor y fecha de creación.
- **CA-2.** Dado que el texto del contenido supera los 500 caracteres, cuando envío, entonces se muestra el mensaje "El comentario no puede superar los 500 caracteres" y no se guarda nada.
- **CA-3.** Dado que el contenido está vacío o solo tiene espacios, cuando envío, entonces se muestra el mensaje "El comentario no puede estar vacío" y no se guarda nada.
- **CA-4.** Dado que el nombre del autor está vacío, tiene menos de 2 caracteres o tiene más de 50, cuando envío, entonces se muestra el mensaje "El nombre del autor debe tener entre 2 y 50 caracteres" y no se guarda nada.
- **CA-5.** Dado que el contenido (sin espacios en los extremos) tiene 1 o 2 caracteres, cuando envío, entonces se muestra el mensaje "El comentario debe tener al menos 3 caracteres" y no se guarda nada.
- **CA-6.** Dado que envío un comentario válido, cuando se guarda, entonces la fecha de creación la asigna el sistema, y cualquier fecha enviada por el usuario se ignora.

**Fuera de alcance de esta feature:** edición y borrado de comentarios, likes y reacciones.

---

### F-COM-002: Listar comentarios

**Historia de usuario:** Como lector, quiero ver los comentarios, para enterarme de la conversación.

**Criterios de aceptación:**

- **CA-1.** Dado que existen comentarios previos, cuando abro la página principal, entonces se muestran ordenados del más nuevo al más viejo por fecha de creación, junto con sus respuestas.
- **CA-2.** Dado que no existen comentarios, cuando abro la página principal, entonces se muestra el mensaje "Aún no hay comentarios".
- **CA-3.** Dado que un comentario tiene respuestas, cuando abro la página principal, entonces sus respuestas se muestran debajo de él, ordenadas de la más antigua a la más nueva.
- **CA-4.** Dado que se muestra un comentario o una respuesta, entonces se ve su fecha y hora de creación con el formato `dd/mm/aaaa hh:mm`.

**Fuera de alcance de esta feature:** paginación, filtros y búsquedas.

---

### F-RES-001: Responder a un comentario

**Historia de usuario:** Como lector, quiero responder a un comentario existente, para aportar sobre lo que dijo otra persona.

**Criterios de aceptación:**

- **CA-1.** Dado que existe un comentario padre y el texto es válido (contenido 3-500 caracteres y autor 2-50 caracteres), cuando envío, entonces la respuesta se guarda y se muestra debajo de su comentario padre con autor y fecha de creación.
- **CA-2.** Dado que el contenido supera los 500 caracteres o está vacío/solo espacios, cuando envío, entonces se rechaza con el mensaje "La respuesta no puede superar los 500 caracteres" o "La respuesta no puede estar vacía" respectivamente, y no se guarda nada.
- **CA-3.** Dado que el nombre del autor es inválido (vacío, menos de 2 o más de 50 caracteres), cuando envío, entonces se rechaza con el mensaje "El nombre del autor debe tener entre 2 y 50 caracteres" y no se guarda nada.
- **CA-4.** Dado que ya existe una respuesta a un comentario, no es posible responder directamente a esa respuesta: la interfaz no ofrece esa opción y el sistema rechaza cualquier intento de hacerlo (solo se puede responder al comentario principal).
- **CA-5.** Dado que el contenido (sin espacios en los extremos) tiene 1 o 2 caracteres, cuando envío, entonces se rechaza con el mensaje "La respuesta debe tener al menos 3 caracteres" y no se guarda nada.
- **CA-6.** Dado que el comentario padre no existe, cuando envío una respuesta, entonces se rechaza con el mensaje "El comentario que intentas responder no existe" y no se guarda nada.
- **CA-7.** Dado que envío una respuesta válida, cuando se guarda, entonces la fecha de creación la asigna el sistema, y cualquier fecha enviada por el usuario se ignora.

**Fuera de alcance de esta feature:** respuestas anidadas de más de un nivel, edición y borrado.

---

## 6. Alcance del proyecto

### DENTRO del alcance (v1)

- Creación de comentarios (F-COM-001)
- Listado de comentarios ordenados del más reciente al más antiguo (F-COM-002)
- Respuestas de un solo nivel (F-RES-001)
- Validaciones de contenido y autor según las reglas de negocio RN-001 a RN-008
- Mensajes de error claros y visibles para el usuario
- Una única conversación global

### FUERA del alcance (futuras versiones)

- Múltiples artículos, hilos o secciones
- Autenticación y registro de usuarios
- Edición y borrado de comentarios o respuestas
- Moderación, aprobación previa o roles (moderador)
- Likes, reacciones y reportes
- Respuestas anidadas (más de 1 nivel de profundidad)
- Paginación o infinite scroll, búsquedas y filtros
- Notificaciones
- Rich text, markdown o emojis extendidos
- Internacionalización
- Diseño mobile-first avanzado

---

## 7. Catálogo de mensajes al usuario

| Situación | Mensaje | Origen |
|---|---|---|
| Comentario > 500 caracteres | El comentario no puede superar los 500 caracteres | F-COM-001 CA-2 |
| Comentario vacío / solo espacios | El comentario no puede estar vacío | F-COM-001 CA-3 |
| Comentario < 3 caracteres | El comentario debe tener al menos 3 caracteres | F-COM-001 CA-5 |
| Respuesta > 500 caracteres | La respuesta no puede superar los 500 caracteres | F-RES-001 CA-2 |
| Respuesta vacía / solo espacios | La respuesta no puede estar vacía | F-RES-001 CA-2 |
| Respuesta < 3 caracteres | La respuesta debe tener al menos 3 caracteres | F-RES-001 CA-5 |
| Autor inválido (comentario o respuesta) | El nombre del autor debe tener entre 2 y 50 caracteres | F-COM-001 CA-4 · F-RES-001 CA-3 |
| Comentario padre inexistente | El comentario que intentas responder no existe | F-RES-001 CA-6 |
| Sin comentarios | Aún no hay comentarios | F-COM-002 CA-2 |

---

## 8. Pendientes de la Semana 3 — estado

Todas las preguntas que quedaron abiertas en la versión 0.3 se resolvieron en `design.md`:

| Pregunta original | Resolución |
|---|---|
| ¿Cómo se identifica el artículo actual? | No hay artículos: conversación única global (§1, §6). Decisión técnica **D-07**. |
| ¿Qué ocurre si se responde a un comentario que ya no existe? | Se rechaza con mensaje (F-RES-001 CA-6). Decisión técnica **D-02**. |
| ¿Cómo se genera y muestra la fecha de creación? | La genera el sistema (RN-007) y se muestra como `dd/mm/aaaa hh:mm` (F-COM-002 CA-4). Decisión técnica **D-08**. |
| ¿Dónde se valida? | Siempre en el servidor; el cliente solo ayuda. Decisión técnica **D-02**. |
</document_content>