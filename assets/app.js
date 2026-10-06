/**
 * app.js — Ayudas de cliente (Vanilla JS, sin frameworks)
 * Paso C (Agente 3: Frontend Dev Agent) — D-09 · AGENTS.md §2 (Agente 3)
 *
 * La app debe funcionar con JS desactivado: este script solo mejora la UX.
 * - Contador de caracteres para los textareas con maxlength.
 * - Deshabilitar el botón de envío al enviar (evita duplicados, D-09).
 */

document.addEventListener('DOMContentLoaded', function () {
    // --- Contador de caracteres (progresivo: el markup está oculto sin JS) ---
    document.querySelectorAll('textarea[maxlength]').forEach(function (campo) {
        var contador = campo.form && campo.form.querySelector('[data-contador="' + campo.name + '"]');
        if (!contador) {
            return;
        }

        var limite = parseInt(campo.getAttribute('maxlength'), 10) || 0;

        function actualizar() {
            contador.textContent = campo.value.length + '/' + limite;
        }

        contador.hidden = false;
        campo.addEventListener('input', actualizar);
        actualizar();
    });

    // --- Botón deshabilitado al enviar (evita doble envío, D-09) ---
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            var boton = form.querySelector('button[type="submit"]');
            if (boton) {
                boton.disabled = true;
            }
        });
    });

    // --- Formularios "Responder" (mejora progresiva, RN-004/D-03/D-09) ---
    // Sin JS quedan todos visibles (la app funciona igual). Con JS se ocultan
    // y "Responder" destapa solo el del comentario elegido. Si el servidor
    // devolvió un 422 de ese formulario (data-visible="1"), queda abierto.
    var formsRespuesta = Array.prototype.slice.call(document.querySelectorAll('form[data-form-respuesta]'));
    if (formsRespuesta.length) {
        var abrirForm = function (idForm) {
            formsRespuesta.forEach(function (form) {
                form.hidden = true;
            });
            var objetivo = document.getElementById(idForm);
            if (objetivo) {
                objetivo.hidden = false;
                var campo = objetivo.querySelector('textarea, input[type="text"]');
                if (campo) {
                    campo.focus();
                }
            }
        };

        // Ocultar todos salvo el que venga marcado como visible tras un 422.
        formsRespuesta.forEach(function (form) {
            form.hidden = form.getAttribute('data-visible') !== '1';
        });

        document.querySelectorAll('a.enlace-responder').forEach(function (enlace) {
            enlace.addEventListener('click', function (evento) {
                evento.preventDefault();
                abrirForm(this.getAttribute('data-form'));
            });
        });
    }

    // Si el usuario vuelve con "atrás" (bfcache), reactivar el botón.
    window.addEventListener('pageshow', function (evento) {
        if (!evento.persisted) {
            return;
        }
        document.querySelectorAll('button[type="submit"][disabled]').forEach(function (boton) {
            boton.disabled = false;
        });
    });
});
