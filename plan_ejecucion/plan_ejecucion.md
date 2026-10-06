# Plan de Ejecución — App de Comentarios

- **Documento:** plan de ejecución y seguimiento
- **Versión:** 0.1
- **Última actualización:** 06/10/2026
- **Estado global:** en curso — F-COM-001 en Paso A
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
| **F-COM-001** | Crear un comentario | 🟡 **En curso** | Paso A ✅ · Paso B ⬜ · Paso C ⬜ |
| F-COM-002 | Listar comentarios | ⬜ Pendiente | 0% (su listado mínimo se arranca de forma transversal en Paso C, por D-09) |
| F-RES-001 | Responder a un comentario | ⬜ Pendiente | 0% |

**Detalle F-COM-001 (la feature que se va completando):**

| Criterio | Descripción | Estado |
|---|---|---|
| CA-1 | Comentario válido → se guarda y aparece en el listado | ⬜ dep. del Paso C |
| CA-2 | > 500 caracteres → mensaje, no se guarda | ⬜ Paso B |
| CA-3 | Vacío / solo espacios → mensaje, no se guarda | ⬜ Paso B |
| CA-4 | Autor inválido → mensaje, no se guarda | ⬜ Paso B |
| CA-5 | Contenido 1-2 caracteres → mensaje, no se guarda | ⬜ Paso B |
| CA-6 | Fecha la asigna el sistema; la del cliente se ignora | ⬜ Paso B |

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
- **Nota de trazabilidad:** esto recorta parcialmente D-01 (que definía las dos tablas). Queda registrado aquí para que la alta de `respuestas` se lea como **ampliación planificada**, no como corrección.

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

---

## 3. Registro de acciones (historial)

| # | Fecha | Acción | Artefacto | Estado |
|---|---|---|---|---|
| 1 | 06/10/2026 | Lectura de specs (`requirements.md`, `design.md`, `AGENTS.md`) | — | ✅ HECHO |
| 2 | 06/10/2026 | Propuesta de implementación de F-COM-001 por capas (Paso A/B/C) | — | ✅ APROBADO |
| 3 | 06/10/2026 | **Paso A (Agente 1):** generación del script SQL | `sql/schema.sql` | ✅ HECHO |
| 4 | — | Importar `sql/schema.sql` en phpMyAdmin (XAMPP) | — | ⬜ PENDIENTE (usuario) |
| 5 | — | Aprobación del schema importado | — | ⬜ PENDIENTE (usuario) |
| 6 | — | **Paso B (Agente 2):** backend PHP | `db.php` · `validacion.php` · `comentarios.php` · `comentar.php` | ⬜ PENDIENTE |
| 7 | — | **Paso C (Agente 3):** frontend | `index.php` · `vistas.php` · `assets/app.js` · `assets/style.css` | ⬜ PENDIENTE |
| 8 | — | Revisión final: checklist de trazabilidad y seguridad | `AGENTS.md` §5 | ⬜ PENDIENTE |

---

## 4. Plan por pasos (Paso A de F-COM-001 completado)

### ✅ Paso A — Agente 1 (Base de datos) — COMPLETADO
- **Propuesto:** `sql/schema.sql` con `utf8mb4` / InnoDB, tabla `comentarios` con `id`, `autor VARCHAR(50)`, `contenido VARCHAR(500)`, `fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP` e índice en `fecha_creacion`.
- **Aprobado:** solo tabla `comentarios` (D-B).
- **Hecho:** archivo generado en `sql/schema.sql`.

### ⬜ Paso B — Agente 2 (Backend) — PENDIENTE
*Se ejecuta tras la aprobación del schema importado.*

| Archivo | Contenido | Refs |
|---|---|---|
| `db.php` | Conexión mysqli, `utf8mb4`, `MYSQLI_REPORT_ERROR \| MYSQLI_REPORT_STRICT`, `SET time_zone = '-03:00'` | D-06, D-08 |
| `validacion.php` | Trim Unicode (`preg_replace('/^\s+\|\s+$/u', '')`), `mb_strlen(..., 'UTF-8')`, orden: autor → vacío → mínimo → máximo, mensajes exactos de `requirements.md` §7 | D-02, RN-001/002/005/008 |
| `comentarios.php` | `INSERT` preparado **sin** columna `fecha_creacion` + `obtener_comentarios()` (necesario para el re-render) | RN-007, D-05, D-09 |
| `comentar.php` | Handler: `405` si no es POST → `422` re-render con mensaje y valores conservados → `303` a `index.php#comentario-{id}` → `500` genérico | D-02, D-09 |

**Mapeo CA → comportamiento (F-COM-001):**

| CA | Validación | Mensaje (§7) | Código |
|---|---|---|---|
| CA-1 | ok → insert | — (sin mensaje de éxito, D-09) | 303 |
| CA-2 | `mb_strlen > 500` | El comentario no puede superar los 500 caracteres | 422 |
| CA-3 | trim → vacío | El comentario no puede estar vacío | 422 |
| CA-4 | autor 2–50 | El nombre del autor debe tener entre 2 y 50 caracteres | 422 |
| CA-5 | trim → 1-2 chars | El comentario debe tener al menos 3 caracteres | 422 |
| CA-6 | fecha solo en DB | — (se ignora la fecha del cliente) | 303 |

### ⬜ Paso C — Agente 3 (Frontend) — PENDIENTE
- `index.php` + `vistas.php`: listado mínimo (comentarios `fecha_creacion DESC, id DESC` — D-05), empty state "Aún no hay comentarios", fecha `d/m/Y H:i` (D-08), `htmlspecialchars` + `nl2br` post-escape (D-06), formulario con `required` / `minlength` / `maxlength` nativos, mensaje de error visible junto al formulario conservando lo escrito.
- `assets/app.js`: solo contador de caracteres y deshabilitar botón al enviar (la app debe funcionar con JS desactivado).
- `assets/style.css`: mínimo.

### ⬜ Revisión — Checklist `AGENTS.md` §5
Pruebas manuales por CA-1 a CA-6 vía formulario **y** vía `curl` (sin JS), incluyendo: fecha fake ignorada (CA-6), contenido de 501 caracteres, autor de 1 carácter y comentario de solo espacios.

---

## 5. Pendientes abiertos

| # | Pendiente | Responsable |
|---|---|---|
| 1 | Importar `sql/schema.sql` en phpMyAdmin y confirmar que funcionó | Usuario |
| 2 | Aprobar el schema para desbloquear el Paso B | Usuario |
| 3 | Registrar la ampliación del schema con la tabla `respuestas` al arrancar F-RES-001 | Agente 1 |
