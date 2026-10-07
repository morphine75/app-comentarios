# Plan de Ejecución — App de Comentarios

- **Documento:** plan de ejecución y seguimiento
- **Versión:** 0.5
- **Última actualización:** 06/10/2026
- **Estado global:** ✅ **v1 funcional** — F-COM-001, F-COM-002 y F-RES-001 implementadas · checklist §5 al 100%
- **Flujo de referencia:** `AGENTS.md` §3 (Paso A → aprobación → Paso B → Paso C → checklist §5)

**Convención de estados:**

| Estado | Significado |
|---|---|
| `PROPUESTO` | Se definió y se presentó al usuario, pendiente de decisión |
| `APROBADO` | El usuario dio su visto bueno |
| `HECHO` | El artefacto quedó generado / la acción se ejecutó |
| `PENDIENTE` | Todavía no arrancó |

---

## 1. Estado de las features

| Feature | Nombre | Estado | Avance |
|---|---|---|---|
| **F-COM-001** | Crear un comentario | ✅ **Completada** | Paso A ✅ · Estructura ✅ · Paso B ✅ · Paso C ✅ (checklist §5 revisado) |
| **F-COM-002** | Listar comentarios | ✅ **Completada** | CA-1 ✅ · CA-2 ✅ · CA-3 (respuestas ASC) ✅ · CA-4 ✅ |
| **F-RES-001** | Responder a un comentario | ✅ **Completada** | CA-1 a CA-7 ✅ (ver detalle) |

**Detalle F-RES-001:**

| Criterio | Descripción | Estado |
|---|---|---|
| CA-1 | Respuesta válida → se guarda y se muestra bajo su padre | ✅ `303 → index.php#respuesta-{id}` |
| CA-2 | > 500 o vacío/solo espacios → "La respuesta…" y no se guarda | ✅ mensajes exactos verificados (422) |
| CA-3 | Autor inválido → mensaje, no se guarda | ✅ 422 + mensaje exacto |
| CA-4 | No se puede responder a una respuesta (UI ni servidor) | ✅ sin botón en respuestas + responder con id de respuesta → 404 |
| CA-5 | Contenido 1-2 caracteres → mensaje, no se guarda | ✅ 422 + mensaje exacto |
| CA-6 | Padre inexistente → mensaje, no se guarda | ✅ 404 + "El comentario que intentas responder no existe" |
| CA-7 | Fecha la asigna el sistema; la del cliente se ignora | ✅ fecha fake ignorada (303) |

**Detalle F-COM-001 (la feature que se va completando):**

| Criterio | Descripción | Estado |
|---|---|---|
| CA-1 | Comentario válido → se guarda y aparece en el listado | ✅ probado (curl) |
| CA-2 | > 500 caracteres → mensaje, no se guarda | ✅ probado (422 + mensaje exacto) |
| CA-3 | Vacío / solo espacios → mensaje, no se guarda | ✅ probado (422 + mensaje exacto) |
| CA-4 | Autor inválido → mensaje, no se guarda | ✅ probado (422 + mensaje exacto) |
| CA-5 | Contenido 1-2 caracteres → mensaje, no se guarda | ✅ probado (422 + mensaje exacto) |
| CA-6 | Fecha la asigna el sistema; la del cliente se ignora | ✅ probado (fecha fake ignorada) |

---

## 2. Registro de decisiones (propuesto → aprobado → hecho)

### D-A — Alcance de la primera tanda de trabajo
- **PROPUESTO:** tres opciones — solo Paso A (schema), Paso A + B, o A + B + C completo.
- **APROBADO (usuario):** **Solo Paso A** (`sql/schema.sql`), esperando aprobación antes de escribir PHP, según `AGENTS.md` §3.
- **HECHO:** —
- **Próximo:** ejecutar Paso B tras la aprobación del schema importado.

### D-B — Alcance del schema inicial
- **PROPUESTO (2 opciones):** generar ambas tablas (`comentarios` + `respuestas`, según D-01) o solo `comentarios`.
- **APROBADO (usuario):** **Solo `comentarios`**; `respuestas` se agregará como ampliación en la feature F-RES-001.
- **HECHO:** `sql/schema.sql` generado con la tabla `comentarios` (y BD `app_comentarios`).
- **SUPERADO por D-E:** la decisión se modificó el mismo día; ver D-E.
- **Nota de trazabilidad:** esto recortó parcialmente D-01 (que definía las dos tablas). El recorte quedó **anulado** con D-E: el schema vuelve a la totalidad de D-01.

### D-E — Ampliación del schema: alta de la tabla `respuestas` (modifica D-B)
- **PROPUESTO (agente):** completar `sql/schema.sql` con la tabla `respuestas` según D-01, D-05 y D-03: `id`, `comentario_padre_id` FK → `comentarios.id` (sin columna de anidamiento, RN-004), `autor VARCHAR(50)`, `contenido VARCHAR(500)`, `fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP`, índice compuesto `(comentario_padre_id, fecha_creacion)`. FK con comportamiento por defecto (`RESTRICT`), ya que el borrado está fuera de alcance en v1.
- **APROBADO (usuario):** ✅ **Agregar `respuestas` ahora** (en lugar de diferirlo a F-RES-001).
- **HECHO:** tabla agregada a `sql/schema.sql`. Queda como **ampliación planificada** de D-B, no como corrección.

### D-C — Nombre de la base de datos
- **PROPUESTO (por el agente):** `app_comentarios` (design.md no lo nombra).
- **APROBADO:** implícito — el usuario no lo objetó al aprobar D-B.
- **HECHO:** aplicado en `sql/schema.sql`.

### D-D — Verificación de contradicciones entre documentos
- **PROPUESTO:** revisar `requirements.md` v0.4 + `design.md` v0.2 + `AGENTS.md` antes de codificar (fase de lectura, §3.1).
- **APROBADO:** —
- **HECHO:** ✅ sin contradicciones. Se detectaron 2 **dependencias** (no conflictos):
  1. F-COM-001 CA-1 exige listado → el Paso C debe incluir `index.php` con listado mínimo desde el arranque.
  2. El re-render de error `422` (D-09) necesita que `comentar.php` pueda volver a dibujar la página con los comentarios existentes.
- Consecuencia: los módulos *Presentación* y *Consulta* (design §3) entran temprano aunque su feature formal sea F-COM-002.

### D-F — Estructura de carpetas y archivos del proyecto
- **PROPUESTO (agente):** esqueleto completo en la raíz del monolito (XAMPP), según `design.md` §1/§3:
  - Paso B: `db.php`, `validacion.php`, `comentarios.php`, `comentar.php`
  - Paso C: `index.php`, `vistas.php`, `assets/app.js`, `assets/style.css`
  - Ampliación F-RES-001: `respuestas.php`, `responder.php`
  - Nueva carpeta: `assets/` (los demás directorios ya existen)
- **APROBADO (usuario):** ✅ "vamos a generar las carpetas y archivos".
- **HECHO:** 10 archivos creados como **stubs** con cabecera que documenta contenido planificado y trazabilidad (RN/D/CA). Sin lógica todavía: los Pasos B y C se ejecutan tras la aprobación del schema importado.

### D-G — Alcance del Paso B: backend + listado mínimo
- **CONTEXT:** el `422` del re-render (D-09) necesita que la página se pueda dibujar, pero `index.php`/`vistas.php` son del Paso C (dependencia registrada en D-D).
- **PROPUESTO (agente, 3 opciones):** solo los 4 archivos backend · backend con render sin listado · **backend + listado mínimo** (dejando el pullo de JS/CSS para el Paso C).
- **APROBADO (usuario):** ✅ **Paso B + listado mínimo**.
- **HECHO:** `index.php` y `vistas.php` implementados en versión mínima (listado + formulario + empty state + escape + fecha `d/m/Y H:i`) para que `comentar.php` pueda devolver `422` re-renderizando la página.

### D-H — Decisiones de implementación del Paso C
- **`novalidate` en el formulario:** el atributo está presente con todos los ayudantes nativos (`required`, `minlength`, `maxlength`), pero `novalidate` queda activo para que el **navegador deje pasar el envío y el usuario vea el mensaje del catálogo §7** (que es lo que exigen los CA). Si el navegador bloqueara, se mostraría un mensaje nativo genérico en lugar del catálogo. Racional: D-02 — el cliente "puede" ayudar pero no es la fuente de verdad; los atributos siguen limitando la entrada (`maxlength` corta el tipeo) y el servidor valida siempre.
- **`obtener_comentarios()` pasó a sentencia preparada** aunque la query no tiene datos dinámicos, para cumplir literalmente el checklist §5 ("todas las consultas usan `prepare` + `bind_param`").
- **Contador de caracteres progresivo:** el `<small data-contador>` va en el HTML con `hidden`; JS lo revela y lo actualiza. Sin JS la app funciona igual (D-09).
- **Botón deshabilitado al enviar** + reactivación en `pageshow` (bfcache) para no dejarlo trabado al volver atrás (D-09).

### D-I — Decisiones de implementación de F-RES-001
- **Ancla del 303 en respuestas:** `index.php#respuesta-{id}` (la respuesta tiene `id="respuesta-{id}"`). El checklist §5 habla de `#comentario-{id}` para el caso de comentarios (F-COM-001); para respuestas el ancla apunta al elemento creado, igual patrón PRG (D-09).
- **Formularios "Responder" en el HTML (uno por comentario)** con enlace `Responder` → `#responder-r{id}`: sin JS permanecen visibles (la app funciona con JS desactivado); con JS (app.js) se ocultan y el enlace destapa solo el elegido. Tras un `422`, `data-visible="1"` en el form del padre hace que JS lo deje abierto con el mensaje y los valores conservados (D-09).
- **Respuestas sin botón ni formulario** (RN-004, D-03): el template nunca renderiza `enlace-responder` dentro de un `article.respuesta`; a nivel servidor, `responder.php` verifica el padre en `comentarios` y un id de respuesta no existe allí → `404` (CA-4).
- **Nuevo `cargar_conversacion()`** en `comentarios.php` como módulo de consulta (design §3): una sola consulta de respuestas ASC agrupadas por padre en PHP (D-05), usada por `index.php`, `comentar.php` y `responder.php` para el re-render.
- **Mensaje 404 en el form principal:** si el padre no existe, el form de ese comentario tampoco → el mensaje "El comentario que intentas responder no existe" se muestra junto al form de comentario, conservando lo escrito (D-09).

---

## 3. Registro de acciones (historial)

| # | Fecha | Acción | Artefacto | Estado |
|---|---|---|---|---|
| 1 | 06/10/2026 | Lectura de specs (`requirements.md`, `design.md`, `AGENTS.md`) | — | ✅ HECHO |
| 2 | 06/10/2026 | Propuesta de implementación de F-COM-001 por capas (Paso A/B/C) | — | ✅ APROBADO |
| 3 | 06/10/2026 | **Paso A (Agente 1):** generación del script SQL | `sql/schema.sql` | ✅ HECHO |
| 4 | 06/10/2026 | **D-E:** ampliación del schema con la tabla `respuestas` (D-01, D-05, D-03) | `sql/schema.sql` | ✅ HECHO |
| 5 | 06/10/2026 | **D-F:** creación de la estructura de archivos (10 stubs + carpeta `assets/`) | `db.php` · `validacion.php` · `comentarios.php` · `comentar.php` · `index.php` · `vistas.php` · `respuestas.php` · `responder.php` · `assets/app.js` · `assets/style.css` | ✅ HECHO |
| 6 | 06/10/2026 | Importar `sql/schema.sql` en phpMyAdmin (XAMPP) + verificación del agente contra MySQL (`SHOW TABLES`: `comentarios` y `respuestas` OK, FK e índice de `respuestas` correctos) | `app_comentarios` | ✅ HECHO |
| 7 | 06/10/2026 | Aprobación del schema importado (usuario) | — | ✅ APROBADO |
| 8 | 06/10/2026 | **D-G:** definición del alcance del Paso B (backend + listado mínimo) | — | ✅ APROBADO |
| 9 | 06/10/2026 | **Paso B (Agente 2):** backend PHP + listado mínimo | `db.php` · `validacion.php` · `comentarios.php` · `comentar.php` · `index.php` · `vistas.php` | ✅ HECHO |
| 10 | 06/10/2026 | Pruebas manuales (curl + Apache): 405 · CA-1 a CA-6 · mensajes exactos del catálogo §7 · 303 con ancla · escape XSS · conservación de valores en 422. Datos de prueba limpiados | — | ✅ HECHO |
| 11 | 06/10/2026 | Aprobación del Paso B / orden de continuar con el Paso C (usuario) | — | ✅ APROBADO |
| 12 | 06/10/2026 | **Paso C (Agente 3):** frontend — `assets/app.js` (contador + botón + bfcache), `assets/style.css` (estilos nativos), contador en `vistas.php`, `obtener_comentarios()` a `prepare` (checklist §5) | `assets/app.js` · `assets/style.css` · `vistas.php` · `comentarios.php` | ✅ HECHO |
| 13 | 06/10/2026 | Pruebas Paso C: `php -l` · assets servidos 200 · contador `34/500` vía navegador · sin errores de consola · flujo PRG completo en navegador (`#comentario-3`) · 422 estilizado con valores conservados · re-test curl (303 + prepare). Datos de prueba limpiados | — | ✅ HECHO |
| 14 | 06/10/2026 | Revisión checklist `AGENTS.md` §5 — ver detalle en §4 | — | ✅ HECHO (ver detalle) |
| 15 | 06/10/2026 | **F-RES-001 (Agente 2 + 3):** respuestas — `respuestas.php` (existe_comentario, guardar_respuesta, obtener_respuestas, agrupar), `responder.php` (405/422/404/303/500), `vistas.php` (forms "Responder" + respuestas ASC), `index.php`/`comentar.php`/`responder.php` + `cargar_conversacion()`, `app.js` (toggle forms), `style.css` | `respuestas.php` · `responder.php` · `vistas.php` · `index.php` · `comentar.php` · `comentarios.php` · `assets/app.js` · `assets/style.css` | ✅ HECHO |
| 16 | 06/10/2026 | Pruebas F-RES-001: CA-1 a CA-7 · mensajes §7 ("La respuesta…") · 404 padre inexistente · RN-004 (responder a respuesta → 404 y sin botón en UI) · 422 con error y valores en el form del padre correcto · fecha fake ignorada · orden ASC verificado en HTML · toggle JS en navegador. Datos de prueba limpiados (se conservó el comentario del usuario) | — | ✅ HECHO |
| 17 | 06/10/2026 | Checklist `AGENTS.md` §5 al **100%** (RN-006, RN-004, respuestas ASC cerradas) | — | ✅ HECHO |

---

## 4. Plan por pasos (Paso A de F-COM-001 completado)

### ✅ Paso A — Agente 1 (Base de datos) — COMPLETADO
- **Propuesto:** `sql/schema.sql` con `utf8mb4` / InnoDB, tabla `comentarios` con `id`, `autor VARCHAR(50)`, `contenido VARCHAR(500)`, `fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP` e índice en `fecha_creacion`.
- **Aprobado:** inicialmente solo tabla `comentarios` (D-B); luego **ampliado a las dos tablas** de D-01 (D-E).
- **Hecho:** archivo generado y completado con la tabla `respuestas` (FK → `comentarios.id`, índice compuesto `(comentario_padre_id, fecha_creacion)`, sin columnas de anidamiento).

### ✅ Estructura de archivos — D-F — COMPLETADO
- 10 stubs creados en la raíz + carpeta `assets/`, cada uno con cabecera que documenta su contenido planificado y sus refs (RN/D/CA). Sin lógica: la implementación corresponde a los Pasos B y C.

### ✅ Paso B — Agente 2 (Backend) — COMPLETADO (D-G)
*Ejecutado tras la aprobación del schema importado.*

| Archivo | Contenido | Refs |
|---|---|---|
| `db.php` | Conexión mysqli, `utf8mb4`, `MYSQLI_REPORT_ERROR \| MYSQLI_REPORT_STRICT`, `SET time_zone = '-03:00'` | D-06, D-08 |
| `validacion.php` | Trim Unicode (`preg_replace('/^\s+\|\s+$/u', '')`), `mb_strlen(..., 'UTF-8')`, orden: autor → vacío → mínimo → máximo, mensajes exactos de `requirements.md` §7 | D-02, RN-001/002/005/008 |
| `comentarios.php` | `INSERT` preparado **sin** columna `fecha_creacion` + `obtener_comentarios()` (necesario para el re-render) | RN-007, D-05, D-09 |
| `comentar.php` | Handler: `405` si no es POST → `422` re-render con mensaje y valores conservados → `303` a `index.php#comentario-{id}` → `500` genérico | D-02, D-09 |
| `index.php` · `vistas.php` | Listado mínimo según D-G (dependencia D-D): escape + `nl2br` post-escape, fecha `d/m/Y H:i`, empty state, formulario con atributos nativos | D-05, D-06, D-08, D-09 |

**Mapeo CA → comportamiento (F-COM-001) — todos probados ✅:**

| CA | Validación | Mensaje (§7) | Código | Prueba |
|---|---|---|---|---|
| CA-1 | ok → insert | — (sin mensaje de éxito, D-09) | 303 | ✅ `Location: index.php#comentario-1` + aparece en el listado |
| CA-2 | `mb_strlen > 500` | El comentario no puede superar los 500 caracteres | 422 | ✅ mensaje exacto verificado |
| CA-3 | trim → vacío | El comentario no puede estar vacío | 422 | ✅ mensaje exacto verificado |
| CA-4 | autor 2–50 | El nombre del autor debe tener entre 2 y 50 caracteres | 422 | ✅ mensaje exacto verificado |
| CA-5 | trim → 1-2 chars | El comentario debe tener al menos 3 caracteres | 422 | ✅ mensaje exacto verificado |
| CA-6 | fecha solo en DB | — (se ignora la fecha del cliente) | 303 | ✅ fecha fake `01/01/2000` ignorada; guardó `CURRENT_TIMESTAMP` |

**Pruebas adicionales:** `405` en GET a `comentar.php` · escape XSS (`<script>` renderizado escapado) · valores conservados en el `422` · `php -l` sin errores en los 6 archivos. Datos de prueba eliminados de la BD.

### ✅ Paso C — Agente 3 (Frontend) — COMPLETADO (D-H)
- `assets/app.js`: contador de caracteres (marcador progresivo `hidden` revelado por JS) y botón deshabilitado al enviar + reactivación en `pageshow`. La app funciona con JS desactivado (D-09).
- `assets/style.css`: estilos mínimos con CSS nativo (sin frameworks): formulario, mensajes de error visibles, tarjetas de comentario, empty state.
- `vistas.php`: marcador de contador agregado; revisión de F-COM-002 (empty state, escape + `nl2br` post-escape, fecha `d/m/Y H:i`).
- `comentarios.php`: `obtener_comentarios()` migrada a `prepare`/`get_result` para el checklist §5.
- **Pruebas:** assets 200 · contador `34/500` en navegador · 0 errores de consola · flujo PRG completo en navegador (`303 → #comentario-3`, ancla y listado OK) · `422` estilizado en navegador con mensaje del catálogo y valores conservados · re-test curl tras el cambio a `prepare` (303 OK) · datos de prueba limpiados.

### ✅ F-RES-001 — Responder a un comentario — COMPLETADO (D-I)
- `respuestas.php`: `existe_comentario()` (SELECT preparado, RN-006), `guardar_respuesta()` (INSERT sin `fecha_creacion`, RN-007), `obtener_respuestas()` (una sola consulta ASC), `agrupar_respuestas()` (D-05).
- `responder.php`: `405` no-POST · `422` mensajes §7 contando "La respuesta…" · `404` padre inexistente (o id de respuesta usado como padre → RN-004) · `303` a `index.php#respuesta-{id}` · `500` genérico con `error_log`.
- `vistas.php`: enlace + form "Responder" solo en comentarios (RN-004), respuesta sin botón, formularios con `data-visible` para el 422.
- `app.js`/`style.css`: toggle progresivo de forms (función sin JS, D-09) y estilos de respuestas.
- **Pruebas:** todos los CA-1..CA-7 ✅ (tabla en §1) · mensajes exactos verificado por curl · `404` en padre `999999` y en un id de respuesta usado como padre · respuestas ASC bajo su padre (índices en HTML) · sin `<a>` dentro de los `article.respuesta` · toggle JS (abre uno, cierra el anterior, foco en autor) · 422 re-render solo en el form del padre con valores · `php -l` sin errores · BD limpia luego de los tests (se conservó el comentario 6 creado por el usuario).

### ✅ Revisión — Checklist `AGENTS.md` §5 — 100%

| Control | Referencia | Resultado |
|---|---|---|
| Todas las consultas usan `prepare` + `bind_param` | D-06 | ✅ (incluidas las de respuestas) |
| Toda salida de datos del usuario pasa por `htmlspecialchars` | D-06 | ✅ (`e()` en `vistas.php`; probado con `<script>`) |
| Servidor valida autor 2–50 y contenido 3–500, tras recortar | RN-001/002/005/008 · D-02 | ✅ |
| Se usa `mb_strlen(..., 'UTF-8')` | D-02 | ✅ |
| Mensajes coinciden con `requirements.md` §7 | F-COM-001, F-RES-001 | ✅ ambos verificados por curl |
| Responder a comentario inexistente → `404` sin guardar | RN-006 | ✅ (padre 999999 y mensaje del catálogo) |
| No hay forma de responder a una respuesta (UI ni servidor) | RN-004 | ✅ (sin botón en respuestas; id de respuesta → 404) |
| La fecha la pone MySQL y la del cliente se ignora | RN-007 · D-08 | ✅ probado (CA-6 y CA-7) |
| Comentarios DESC, respuestas ASC, desempate por `id` | D-05 · F-COM-002 | ✅ verificado en HTML |
| Éxito → `303` a `index.php#comentario-{id}`; error → `422`/`404` sin redirigir | D-09 | ✅ comentarios y respuestas |
| No hay `articulo_id`, sesiones, Composer ni frameworks | D-07 · §1 | ✅ |

---

## 5. Pendientes abiertos

| # | Pendiente | Responsable |
|---|---|---|
| 1 | ~~Importar `sql/schema.sql` en phpMyAdmin (XAMPP) y confirmar que funcionó~~ — verificado por el agente | ✅ HECHO |
| 2 | ~~Aprobar el schema importado para desbloquear el Paso B~~ — aprobado el 06/10/2026 | ✅ HECHO |
| 3 | ~~Registrar la ampliación del schema con la tabla `respuestas`~~ — resuelto por D-E | ✅ HECHO |
| 4 | ~~Aprobar el Paso B~~ — aprobado ("continua") y Paso C ejecutado | ✅ HECHO |
| 5 | ~~Ejecutar **F-RES-001**~~ — completada el 06/10/2026 (CA-1 a CA-7 ✅) | ✅ HECHO |
| 6 | Prueba final del usuario en el navegador (local y/o phpMyAdmin) y visto bueno de la v1 | Usuario |
| 7 | (Futuro, fuera de v1) Edición/borrado, moderación, likes, múltiples artículos… | — |
