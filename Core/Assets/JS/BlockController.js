/*!
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Comportamiento de las páginas de BlockController: carga perezosa de bloques y cabecera.
 */
(function () {
    'use strict';

    var observer = null;

    // cada hueco .block-lazy pide su bloque cuando entra en pantalla, lo que incluye
    // el momento en que se abre la pestaña que lo contiene
    function loadBlock(element) {
        if (element.dataset.blockState) {
            return;
        }
        element.dataset.blockState = 'loading';

        $.get(element.dataset.blockUrl).done(function (html) {
            // jQuery ejecuta los scripts que trae el bloque, que las vistas necesitan
            var $content = $('<div></div>').html(html);
            $(element).replaceWith($content);
            watch($content[0]);
        }).fail(function () {
            // dejamos un botón para reintentarlo
            delete element.dataset.blockState;
            $(element).html('<button type="button" class="btn btn-sm btn-outline-secondary">'
                + '<i class="fa-solid fa-rotate-right" aria-hidden="true"></i></button>');
            $(element).find('button').on('click', function () {
                loadBlock(element);
            });
        });
    }

    function watch(root) {
        root.querySelectorAll('.block-lazy').forEach(function (element) {
            if (observer) {
                observer.observe(element);
            } else {
                loadBlock(element);
            }
        });
    }

    // los botones de la cabecera envían su acción con el formulario de la cabecera
    function headerAction(button) {
        var form = document.getElementById('formBlockHeader');
        form.querySelector('input[name="block"]').value = button.dataset.headerBlock || '';

        if (button.dataset.headerConfirm) {
            confirmAction('BlockHeader', button.dataset.headerAction, button.dataset.headerLabel,
                form.dataset.confirmMessage, form.dataset.confirmCancel, form.dataset.confirmAccept,
                button.dataset.headerIcon, 'btn-' + (button.dataset.headerColor || 'danger'));
            return;
        }

        form.querySelector('input[name="action"]').value = button.dataset.headerAction;
        animateSpinner('add');
        form.submit();
    }

    // lleva a un bloque, abriendo antes las pestañas que lo contienen
    function goToBlock(name) {
        var block = document.getElementById('block-' + name);
        if (!block) {
            return;
        }

        var panes = [];
        for (var pane = block.closest('.tab-pane'); pane; pane = pane.parentElement.closest('.tab-pane')) {
            panes.unshift(pane);
        }
        // una pestaña no ocupa su sitio hasta que termina de abrirse, así que esperamos a la última
        var pending = null;
        panes.forEach(function (pane) {
            var trigger = document.querySelector('[data-bs-target="#' + pane.id + '"]');
            if (trigger && !pane.classList.contains('active')) {
                pending = trigger;
                bootstrap.Tab.getOrCreateInstance(trigger).show();
            }
        });

        function scroll() {
            block.scrollIntoView({behavior: 'smooth', block: 'start'});
        }

        if (pending) {
            pending.addEventListener('shown.bs.tab', scroll, {once: true});
        } else {
            scroll();
        }
    }

    // la cabecera se queda fija debajo de la barra superior, y los saltos a un bloque le dejan sitio
    function measureHeader() {
        var navbar = document.querySelector('nav.navbar');
        var header = document.querySelector('.block-header-sticky');
        var top = navbar ? navbar.offsetHeight : 0;
        document.documentElement.style.setProperty('--block-header-top', top + 'px');
        document.documentElement.style.setProperty('--block-scroll-margin', (top + (header ? header.offsetHeight : 0) + 8) + 'px');
    }

    // recordamos la página desde la que se llegó a esta, para poder volver a ella aunque
    // después se guarde, se recargue o se navegue entre registros
    function setupBack() {
        var back = document.getElementById('blockHeaderBack');
        if (!back) {
            return;
        }

        var key = 'fs-back-' + location.pathname;
        try {
            var referrer = document.referrer ? new URL(document.referrer) : null;
            if (referrer && referrer.origin === location.origin && referrer.pathname !== location.pathname) {
                sessionStorage.setItem(key, referrer.pathname + referrer.search);
            }

            var url = sessionStorage.getItem(key);
            var listPath = back.dataset.listUrl ? new URL(back.dataset.listUrl, location.href).pathname : '';
            if (url && new URL(url, location.href).pathname !== listPath) {
                back.href = url;
                back.classList.remove('d-none');
            }
        } catch (error) {
            // sin sessionStorage no hay botón de volver
        }
    }

    // avisamos de que hay cambios sin guardar en los formularios de edición
    function setupDirty() {
        var badge = document.getElementById('blockHeaderDirty');
        if (!badge) {
            return;
        }

        function isEditForm(form) {
            var action = form ? form.querySelector('input[name="action"]') : null;
            return action && ['edit', 'insert'].indexOf(action.value) !== -1;
        }

        document.addEventListener('input', function (event) {
            if (isEditForm(event.target.form)) {
                badge.classList.remove('d-none');
            }
        });
        document.addEventListener('reset', function (event) {
            if (isEditForm(event.target)) {
                badge.classList.add('d-none');
            }
        });
    }

    function setupCopyLink() {
        var button = document.getElementById('blockHeaderCopyLink');
        if (!button || !navigator.clipboard) {
            return;
        }

        button.addEventListener('click', function () {
            navigator.clipboard.writeText(new URL(button.dataset.url, location.href).href).then(function () {
                // el icono confirma durante un momento que el enlace se ha copiado
                var icon = button.querySelector('i');
                icon.className = 'fa-solid fa-check fa-fw me-2 text-success';
                setTimeout(function () {
                    icon.className = 'fa-solid fa-link fa-fw me-2';
                }, 2000);
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if ('IntersectionObserver' in window) {
            observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        observer.unobserve(entry.target);
                        loadBlock(entry.target);
                    }
                });
            }, {rootMargin: '200px'});
        }
        watch(document);

        document.addEventListener('click', function (event) {
            var action = event.target.closest('[data-header-action]');
            if (action) {
                headerAction(action);
                return;
            }

            var link = event.target.closest('[data-block-goto]');
            if (link) {
                event.preventDefault();
                goToBlock(link.dataset.blockGoto);
            }
        });

        measureHeader();
        window.addEventListener('resize', measureHeader);
        setupBack();
        setupDirty();
        setupCopyLink();
    });
})();
