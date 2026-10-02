# 💬 App de Comentarios

Proyecto base para el **sprint de Vibe Coding** de la Semana 1.
Trayecto: **Arquitectos de Orquestación e Ingeniería IA**.

> ⚠️ **Este proyecto está incompleto a propósito** — te falta agregar la feature que el docente te pida. Vas a hacerlo vos (con ayuda de OpenCode) durante la actividad. **No mires la solucion de nadie.**

---

## 🚀 Cómo empezar

### 1. Descargar el proyecto

Desde una terminal, **cloná el repositorio** (o descargalo en ZIP y descomprimilo):

```bash
git clone <URL_DEL_REPO> app-comentarios
cd app-comentarios
```

> 🔗 El link del repo lo vas a recibir por mail / cartelera / tema. Si estás en el aula,
> la carpeta también va a estar en `silicon/app-comentarios/`.

### 2. Abrir con OpenCode

En la misma carpeta del proyecto, ejecutá:

```bash
opencode
```

Esto abre OpenCode con el proyecto cargado. Confirmá que el árbol de archivos aparezca
a la izquierda (`api/`, `css/`, `js/`, `sql/`).

### 3. Preparar la base de datos (solo la primera vez)

La app necesitá una base MySQL. Abrí XAMPP (o tu servidor), iniciá **Apache y MySQL**, y desde
`http://localhost/phpmyadmin/` ejecutá el script:

```sql
-- abrí este archivo y ejecutalo en phpMyAdmin
sql/schema.sql
```

> Usa usuario `root` sin contraseña por defecto. Si tu configuración es distinta,
> actualizá los valores en `config.php`.

### 4. Probar que la app funcione

Abrí `http://localhost/app-comentarios/index.html` en tu navegador.
Deberías ver el formulario y la lista de comentarios con 3 ejemplos precargados.

---

## ⚙️ Stack del proyecto

| Capa | Tecnología |
|---|---|
| Frontend | HTML5 + CSS nativo + JavaScript vanilla (`fetch`) |
| Backend | PHP 8 (`api/comments.php` — API REST) |
| Base de datos | MySQL 8 (`sql/schema.sql`) |
| Estructura | `api/` + `css/` + `js/` + `sql/` |

⚠️ **No usa frameworks, no usa bundlers, no usa librerías externas.** CSS y JS vanilla a propósito.

---

## 📁 Estructura de carpetas

```
app-comentarios/
├── index.html          → página principal (formulario + lista)
├── config.php          → credenciales de la base de datos
├── api/
│   └── comments.php    → API REST de comentarios (GET/POST/DELETE)
├── css/
│   └── style.css       → estilos nativos, variables CSS
├── js/
│   └── app.js          → lógica de frontend (fetch + render)
└── sql/
    └── schema.sql      → crear BD, tabla e insertar datos de ejemplo
```

---

## ✏️ La actividad (resumen)

Durante el sprint vas a pedirle a OpenCode que agregue una feature **sin contexto
y sin especificación** (por ejemplo: *"agregá la posibilidad de responder a un comentario"*).
El objetivo es que **falle** para detectar los síntomas del vibe coding.

Anotá en la guía de la actividad (`actividad/index.html`) qué problemas detectás:
si inventa archivos, viola el estilo, genera código que rompe lo ya hecho, no valida
inputs, no escribe tests, etc. Después lo debatimos en grupo.

---

## ❓ FAQ

**¿No pude instalar OpenCode?** Podés hacer el sprint con ChatGPT o Gemini pegando el
contenido del proyecto en el chat (ver FAQ en el material de lectura).

**¿Necesito algo más?** Node.js y Git ya configurados (ver checklist del material de lectura).

**¿Rompo algo?** No pasa nada: **es justamente para eso**. Guardá una copia del repo antes
de empezar si querés, o hacé `git reset --hard` para volver al estado original.

---

*Trayecto: Arquitectos de Orquestación e Ingeniería IA · Mgter. Ing. Agustín Encina · 2026*
