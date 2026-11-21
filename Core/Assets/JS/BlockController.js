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
 * Carga perezosa de los bloques de BlockController: cada hueco .block-lazy pide su bloque
 * cuando entra en pantalla, lo que incluye el momento en que se abre la pestaña que lo contiene.
 */
(function () {
    'use strict';

    var observer = null;

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
    });
})();
