# requirements.md — App de Comentarios

- **Nombre del proyecto:** App de Comentarios
- **Versión del documento:** 0.2
- **Estado:** LISTO PARA REVISIÓN
- **Semana:** 3 — SDD I (Spec First)

> **Nota:** este es el documento de especificación (el QUÉ).  
> NO escribas aquí código, arquitectura ni decisiones técnicas (eso es architect.md, Semana 4).

---

## 1. Contexto y objetivos

Aplicación web que permite a los usuarios dejar comentarios y respuestas sobre los mismos comentarios.  

Está pensada para un eso general de escala mínima que replica el caso de uso real de comentarios en medios digitales, por ejemplo. Los usuarios son lectores anónimos que solo deben indicar un nombre visible. El sistema existe para facilitar una conversación pública, simple y controlada alrededor del contenido de un comentario.

---

## 2. Glosario

- **Comentario:** aporte de texto publicado por un lector.
- **Respuesta:** aporte de texto vinculado a un comentario padre (máximo 1 nivel de profundidad).
- **Autor:** nombre libre que el lector escribe al publicar (no es un usuario registrado del sistema).
- **Moderador:** rol que podría aprobar, editar o eliminar comentarios. **Fuera de alcance en v1**.

---

## 3. Entidades del dominio

### Comentario
- `id` — identificador único
- `autor` — nombre visible (2 a 50 caracteres)
- `contenido` — texto de 3 a 500 caracteres
- `fechaCreacion` — fecha y hora de creación (generada por el sistema)

### Respuesta
- `id` — identificador único
- `comentarioPadreId` — identificador del comentario al que responde
- `autor` — nombre visible (2 a 50 caracteres)
- `contenido` — texto de 3 a 500 caracteres
- `fechaCreacion` — fecha y hora de creación (generada por el sistema)

### Relaciones
- Un Comentario puede tener cero o muchas Respuestas.
- Una Respuesta pertenece a exactamente un Comentario.
- Las Respuestas solo tienen 1 nivel de profundidad (no se puede responder a una respuesta).

---

## 4. Reglas de negocio (RN-xxx)

- **RN-001** El contenido de un comentario o respuesta nunca supera los 500 caracteres.
- **RN-002** No se guardan comentarios ni respuestas vacíos o que sean solo espacios en blanco.
- **RN-003** Todo comentario y respuesta registra obligatoriamente el nombre del autor y su fecha de creación.
- **RN-004** Las respuestas solo pueden tener un nivel de profundidad (no se responde a una respuesta).
- **RN-005** El nombre del autor es obligatorio y debe tener entre 2 y 50 caracteres.
- **RN-006** Una respuesta solo puede crearse sobre un comentario que exista.
- **RN-007** La fecha de creación se genera automáticamente por el sistema; el usuario no la ingresa.

---

## 5. Features (F-xxx-000)

### F-COM-001: Crear un comentario

**Historia de usuario:** Como lector, quiero escribir un comentario, para compartir mi opinión.

**Criterios de aceptación:**

- **CA-1.** Dado que el campo de contenido tiene entre 3 y 500 caracteres y el autor tiene entre 2 y 50 caracteres, cuando envío el formulario, entonces el comentario se guarda y se muestra en el listado con autor y fecha de creación.
- **CA-2.** Dado que el texto del contenido supera los 500 caracteres, cuando envío, entonces se muestra el mensaje "El comentario no puede superar los 500 caracteres" y no se guarda nada.
- **CA-3.** Dado que el contenido está vacío o solo tiene espacios, cuando envío, entonces el sistema informa el error y no se guarda nada.
- **CA-4.** Dado que el nombre del autor está vacío o tiene menos de 2 caracteres, cuando envío, entonces el sistema informa el error y no se guarda nada.

**Fuera de alcance de esta feature:** edición y borrado de comentarios, likes y reacciones.

---

### F-COM-002: Listar comentarios

**Historia de usuario:** Como lector, quiero ver los comentarios, para enterarme de la conversación.

**Criterios de aceptación:**

- **CA-1.** Dado que existen comentarios previos, cuando abro el artículo, entonces se muestran ordenados del más nuevo al más viejo por fecha de creación, junto con sus respuestas.
- **CA-2.** Dado que no existen comentarios, cuando abro el artículo, entonces se muestra el mensaje "Aún no hay comentarios".

**Fuera de alcance de esta feature:** paginacion, filtros y busquedas.

---

### F-RES-001: Responder a un comentario

**Historia de usuario:** Como lector, quiero responder a un comentario existente, para aportar sobre lo que dijo otra persona.

**Criterios de aceptación:**

- **CA-1.** Dado que existe un comentario padre y el texto es válido (contenido 3-500 caracteres y autor 2-50 caracteres), cuando envío, entonces la respuesta se guarda y se muestra debajo de su comentario padre.
- **CA-2.** Dado que el texto del contenido supera el máximo o está vacío/solo espacios, cuando envío, entonces se rechaza con el mensaje correspondiente y no se guarda nada.
- **CA-3.** Dado que el nombre del autor es inválido, cuando envío, entonces se rechaza con el mensaje correspondiente y no se guarda nada.
- **CA-4.** Dado que ya existe una respuesta a un comentario, no es posible responder directamente a esa respuesta (solo se puede responder al comentario principal).

**Fuera de alcance de esta feature:** respuestas anidadas de más de un nivel, edición y borrado.


## 6. Alcance del proyecto

### DENTRO del alcance (v1)

- Creación de comentarios (F-COM-001)
- Listado de comentarios ordenados del más reciente al más antiguo (F-COM-002)
- Respuestas de un solo nivel (F-RES-001)
- Validaciones de contenido y autor según las reglas de negocio RN-001 a RN-007
- Mensajes de error claros y visibles para el usuario

### FUERA del alcance (futuras versiones)

- Autenticación y registro de usuarios
- Edición y borrado de comentarios o respuestas
- Moderación, aprobación previa o roles (moderador)
- Likes, reacciones y reportes
- Respuestas anidadas (más de 1 nivel de profundidad)
- Paginación o infinite scroll, busquedas y filtros
- Notificaciones
- Rich text, markdown o emojis extendidos
- Internacionalización
- Diseño mobile-first avanzado

---

## 7. Pendientes para Semana 4 (architect.md)

- ¿Cómo se identifica el artículo actual si no existe un listado ni gestión de artículos?
- ¿Qué ocurre si se intenta crear una respuesta sobre un comentario que ya no existe?
- ¿Cómo se generará y mostrará la fecha de creación de forma consistente?
- ¿Se debe validar en el frontend, en el backend o en ambos?