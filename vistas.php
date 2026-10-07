<?php
/**
 * vistas.php — Módulo de presentación (render)
 * F-COM-001/F-COM-002 + F-RES-001 — Agente 3
 * Refs: D-03, D-05, D-06, D-08, D-09 · F-COM-002 · F-RES-001 CA-4
 *
 * - Toda salida de datos del usuario pasa por htmlspecialchars (D-06).
 * - nl2br() DESPUÉS del escape (D-06).
 * - Fecha formato d/m/Y H:i con DateTime (D-08).
 * - Formulario "Responder" SOLO bajo comentarios, nunca bajo respuestas (RN-004, D-03):
 *   con JS oculto hasta clickear; sin JS queda visible (la app funciona sin JS, D-09).
 * - Respuestas debajo de su padre, orden ASC con desempate por id (D-05).
 * - Empty state "Aún no hay comentarios" (F-COM-002 CA-2).
 * - $origen indica junto a qué formulario mostrar el error y conservar valores:
 *   'comentario' (form principal) o 'comentario-{id}' (form de respuesta de ese padre).
 */

declare(strict_types=1);

/**
 * Escape de salida (D-06).
 */
function e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

/**
 * Fecha dd/mm/aaaa hh:mm (D-08, F-COM-002 CA-4).
 */
function fecha_formateada(string $fecha): string
{
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $fecha);
    if ($dt === false) {
        $dt = new DateTime($fecha);
    }
    $dt->setTimezone(new DateTimeZone('America/Argentina/Buenos_Aires'));

    return $dt->format('d/m/Y H:i');
}

/**
 * Bloque de campos del formulario (reutilizado por comentario y respuesta).
 */
function pintar_campos(string $idSufijo, string $autor, string $contenido): void
{
    $idAutor     = $idSufijo === '' ? 'autor' : 'autor-' . $idSufijo;
    $idContenido = $idSufijo === '' ? 'contenido' : 'contenido-' . $idSufijo;
    ?>
                <label for="<?= e($idAutor) ?>">Nombre</label>
                <input
                    type="text"
                    id="<?= e($idAutor) ?>"
                    name="autor"
                    value="<?= e($autor) ?>"
                    required
                    minlength="2"
                    maxlength="50"
                >

                <label for="<?= e($idContenido) ?>">Contenido</label>
                <textarea
                    id="<?= e($idContenido) ?>"
                    name="contenido"
                    rows="3"
                    required
                    minlength="3"
                    maxlength="500"
                ><?= e($contenido) ?></textarea>
                <small class="contador" data-contador="contenido" hidden aria-hidden="true"></small>
    <?php
}

/**
 * Dibuja la página completa: formulario principal + listado con respuestas.
 *
 * @param array       $comentarios          Filas de obtener_comentarios() (DESC).
 * @param array       $respuestasAgrupadas  comentario_padre_id => respuestas (ASC).
 * @param string|null $error                Mensaje del catálogo §7.
 * @param string      $autor                Valor conservado del autor (re-render).
 * @param string      $contenido            Valor conservado del contenido (re-render).
 * @param string      $origen               'comentario' | 'comentario-{id}'.
 */
function dibujar_pagina(
    array $comentarios,
    array $respuestasAgrupadas = [],
    ?string $error = null,
    string $autor = '',
    string $contenido = '',
    string $origen = 'comentario'
): void {
    $errorEnFormPrincipal = $error !== null && $origen === 'comentario';
    ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>App de Comentarios</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="contenedor">
        <h1>Comentarios</h1>

        <section class="formulario" aria-labelledby="titulo-form">
            <h2 id="titulo-form">Dejá tu comentario</h2>

            <?php if ($errorEnFormPrincipal): ?>
                <p class="mensaje-error" role="alert"><?= e($error) ?></p>
            <?php endif; ?>

            <form action="comentar.php" method="post" novalidate>
                <?php pintar_campos('', $errorEnFormPrincipal ? $autor : '', $errorEnFormPrincipal ? $contenido : ''); ?>
                <button type="submit">Comentar</button>
            </form>
        </section>

        <section class="listado" aria-label="Listado de comentarios">
            <?php if ($comentarios === []): ?>
                <p class="vacio">Aún no hay comentarios</p>
            <?php else: ?>
                <?php foreach ($comentarios as $c): ?>
                    <?php
                    $idComentario   = (int) $c['id'];
                    $origenEste     = 'comentario-' . $idComentario;
                    $errorAqui      = ($error !== null && $origen === $origenEste) ? $error : null;
                    $autorAqui      = $errorAqui !== null ? $autor : '';
                    $contenidoAqui  = $errorAqui !== null ? $contenido : '';
                    $respuestasHijas = $respuestasAgrupadas[$idComentario] ?? [];
                    ?>
                    <article class="comentario" id="comentario-<?= $idComentario ?>">
                        <header>
                            <strong><?= e($c['autor']) ?></strong>
                            <time datetime="<?= e($c['fecha_creacion']) ?>"><?= fecha_formateada($c['fecha_creacion']) ?></time>
                        </header>
                        <div class="contenido"><?= nl2br(e($c['contenido'])) ?></div>

                        <a class="enlace-responder" href="#responder-r<?= $idComentario ?>"
                           data-form="responder-r<?= $idComentario ?>">Responder</a>

                        <form class="formulario form-respuesta"
                              id="responder-r<?= $idComentario ?>"
                              data-form-respuesta
                              data-visible="<?= $errorAqui !== null ? '1' : '0' ?>"
                              action="responder.php" method="post" novalidate>
                            <h3>Responder a <?= e($c['autor']) ?></h3>

                            <?php if ($errorAqui !== null): ?>
                                <p class="mensaje-error" role="alert"><?= e($errorAqui) ?></p>
                            <?php endif; ?>

                            <input type="hidden" name="comentario_padre_id" value="<?= $idComentario ?>">
                            <?php pintar_campos('r' . $idComentario, $autorAqui, $contenidoAqui); ?>
                            <button type="submit">Responder</button>
                        </form>

                        <?php if ($respuestasHijas !== []): ?>
                            <div class="respuestas">
                                <?php foreach ($respuestasHijas as $r): ?>
                                    <article class="respuesta" id="respuesta-<?= (int) $r['id'] ?>">
                                        <header>
                                            <strong><?= e($r['autor']) ?></strong>
                                            <time datetime="<?= e($r['fecha_creacion']) ?>"><?= fecha_formateada($r['fecha_creacion']) ?></time>
                                        </header>
                                        <div class="contenido"><?= nl2br(e($r['contenido'])) ?></div>
                                        <!-- Sin botón "Responder": RN-004, D-03 -->
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <script src="assets/app.js" defer></script>
</body>
</html>
    <?php
}
