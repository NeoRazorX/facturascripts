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

    // bloque al que se está saltando mientras todavía no ha cargado su contenido
    var pendingGoto = null;

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

            // el contenido trae su total, que puede haber cambiado desde que se pidió el contador
            $content[0].querySelectorAll('[data-block-total]').forEach(function (total) {
                setCount(total.dataset.blockName, parseInt(total.dataset.blockTotal, 10));
            });

            // avisamos a los scripts de los bloques, como el de los mapas
            document.dispatchEvent(new CustomEvent('block:loaded', {detail: {root: $content[0]}}));

            // el bloque ya tiene su tamaño real: repetimos el salto que quedó a medias
            if (pendingGoto && pendingGoto.contains($content[0])) {
                pendingGoto.scrollIntoView({behavior: 'smooth', block: 'start'});
                pendingGoto = null;
            }
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

    // pone el contador de un bloque en su insignia; una pestaña sin registros queda atenuada
    function setCount(name, count) {
        document.querySelectorAll('[data-block-count="' + name + '"]').forEach(function (badge) {
            badge.textContent = count;
            badge.classList.toggle('d-none', count === 0);
            delete badge.dataset.blockCountPending;

            var tab = badge.closest('.nav-link');
            if (tab) {
                tab.classList.toggle('block-tab-empty', count === 0);
            }
        });
    }

    // los bloques aplazados no saben cuántos registros tienen: los pedimos todos en una petición
    function loadCounts() {
        var pending = document.querySelectorAll('[data-block-count-pending]');
        if (pending.length === 0) {
            return;
        }

        var names = [];
        pending.forEach(function (badge) {
            if (names.indexOf(badge.dataset.blockCount) === -1) {
                names.push(badge.dataset.blockCount);
            }
        });

        var url = pending[0].dataset.blockCountUrl;
        $.getJSON(url + (url.indexOf('?') === -1 ? '?' : '&') + 'counts=' + names.join(',')).done(function (counts) {
            Object.keys(counts).forEach(function (name) {
                // si el bloque ya ha cargado mientras tanto, su total es más reciente
                if (document.querySelector('[data-block-count="' + name + '"][data-block-count-pending]')) {
                    setCount(name, counts[name]);
                }
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
        var send = function () {
            permitUnload();
            animateSpinner('add');
            form.submit();
        };

        var pending = dirtyForms();
        if (pending.length > 0) {
            confirmLosingChanges(pending, send);
        } else {
            send();
        }
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
        var opened = false;
        panes.forEach(function (pane) {
            var trigger = document.querySelector('[data-bs-target="#' + pane.id + '"]');
            if (trigger && !pane.classList.contains('active')) {
                bootstrap.Tab.getOrCreateInstance(trigger).show();
                opened = true;
            }
        });

        // un bloque que aún no ha cargado apenas ocupa sitio: al cargar repetiremos el salto
        pendingGoto = block.querySelector('.block-lazy') ? block : null;

        function scroll() {
            block.scrollIntoView({behavior: 'smooth', block: 'start'});
        }

        // bootstrap coloca la pestaña al abrirla, pero dejamos que el navegador recalcule antes de desplazar
        if (opened) {
            setTimeout(scroll, 0);
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

    // --- cambios sin guardar ---
    // Un formulario de edición tiene cambios cuando sus valores difieren de los que tenía
    // antes de que el usuario lo tocara. Mientras alguno los tenga se muestra una barra fija,
    // se marcan el formulario y su pestaña, y se avisa antes de salir de la página.
    var DIRTY_IGNORED = ['multireqtoken', 'action', 'activetab', 'code'];
    var dirtySnapshots = new Map();
    var allowUnload = false;

    function isEditForm(form) {
        var action = form ? form.querySelector('input[name="action"]') : null;
        return !!action && ['edit', 'insert'].indexOf(action.value) !== -1;
    }

    function serializeForm(form) {
        var parts = [];
        new FormData(form).forEach(function (value, key) {
            if (DIRTY_IGNORED.indexOf(key) === -1) {
                // de un archivo solo importa si se ha elegido otro
                parts.push(key + '=' + (value instanceof File ? value.name + ':' + value.size : value));
            }
        });
        return parts.join('&');
    }

    // la foto se toma en la primera interacción: para entonces los widgets ya han puesto sus valores
    function watchForm(form) {
        if (!form || dirtySnapshots.has(form) || !isEditForm(form)) {
            return;
        }
        dirtySnapshots.set(form, serializeForm(form));

        // form.submit() no emite el evento submit, así que lo anotamos aquí
        form.submit = function () {
            if (dirtyForms(form).length === 0) {
                permitUnload();
            }
            HTMLFormElement.prototype.submit.call(form);
        };
    }

    function dirtyForms(except) {
        var forms = [];
        dirtySnapshots.forEach(function (snapshot, form) {
            if (form !== except && document.contains(form) && serializeForm(form) !== snapshot) {
                forms.push(form);
            }
        });
        return forms;
    }

    function formBlock(form) {
        return form.closest('.block-anchor');
    }

    function formTitle(form) {
        var block = formBlock(form);
        return (block && block.dataset.blockTitle) || document.title;
    }

    // la navegación que provoca el propio guardado no debe avisar
    function permitUnload() {
        allowUnload = true;
        setTimeout(function () {
            allowUnload = false;
        }, 2000);
    }

    function refreshDirty() {
        var bar = document.getElementById('blockDirtyBar');
        if (!bar) {
            return;
        }

        var forms = dirtyForms();
        document.querySelectorAll('.block-form-dirty').forEach(function (form) {
            form.classList.remove('block-form-dirty');
        });
        document.querySelectorAll('.block-tab-dirty').forEach(function (tab) {
            tab.classList.remove('block-tab-dirty');
        });

        var list = document.getElementById('blockDirtyList');
        list.textContent = '';
        forms.forEach(function (form, index) {
            form.classList.add('block-form-dirty');
            for (var pane = form.closest('.tab-pane'); pane; pane = pane.parentElement.closest('.tab-pane')) {
                var trigger = document.querySelector('[data-bs-target="#' + pane.id + '"]');
                if (trigger) {
                    trigger.classList.add('block-tab-dirty');
                }
            }

            // cada formulario con cambios se nombra con un enlace que lleva a él
            var block = formBlock(form);
            var link = document.createElement('a');
            link.href = '#' + (block ? block.id : '');
            link.className = 'link-dark';
            link.dataset.blockGoto = block ? block.dataset.blockName : '';
            link.textContent = formTitle(form);
            if (index > 0) {
                list.appendChild(document.createTextNode(', '));
            }
            list.appendChild(link);
        });

        // solo se puede guardar desde la barra si los cambios están en un único formulario
        document.getElementById('blockDirtySave').classList.toggle('d-none', forms.length !== 1);
        bar.classList.toggle('d-none', forms.length === 0);
        document.body.classList.toggle('block-dirty-open', forms.length > 0);
    }

    // al enviar un formulario, los cambios de los demás se pierden: pedimos confirmación
    function confirmLosingChanges(others, onContinue) {
        var bar = document.getElementById('blockDirtyBar');
        var previous = document.getElementById('blockDirtyModal');
        if (previous) {
            previous.remove();
        }

        var modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'blockDirtyModal';
        modal.tabIndex = -1;
        modal.innerHTML = '<div class="modal-dialog"><div class="modal-content">'
            + '<div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-pen fa-fw me-1"></i></h5>'
            + '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>'
            + '<div class="modal-body"><p></p><ul class="mb-0"></ul></div>'
            + '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal"></button>'
            + '<button type="button" class="btn btn-warning" id="blockDirtyContinue"></button></div>'
            + '</div></div>';
        modal.querySelector('.modal-title').appendChild(document.createTextNode(bar.dataset.title));
        modal.querySelector('.modal-body p').textContent = bar.dataset.warning;
        others.forEach(function (form) {
            var item = document.createElement('li');
            item.textContent = formTitle(form);
            modal.querySelector('ul').appendChild(item);
        });
        modal.querySelector('.modal-footer .btn-secondary').textContent = bar.dataset.cancel;
        modal.querySelector('#blockDirtyContinue').textContent = bar.dataset.continue;
        document.body.appendChild(modal);

        var instance = new bootstrap.Modal(modal);
        modal.querySelector('#blockDirtyContinue').addEventListener('click', function () {
            instance.hide();
            onContinue();
        });
        modal.addEventListener('hidden.bs.modal', function () {
            modal.remove();
        });
        instance.show();
    }

    function setupDirty() {
        if (!document.getElementById('blockDirtyBar')) {
            return;
        }

        ['focusin', 'pointerdown', 'keydown'].forEach(function (type) {
            document.addEventListener(type, function (event) {
                watchForm(event.target.form || (event.target.closest ? event.target.closest('form') : null));
            }, true);
        });
        ['input', 'change'].forEach(function (type) {
            document.addEventListener(type, refreshDirty);
        });
        document.addEventListener('reset', function () {
            // los valores se restauran después del evento
            setTimeout(refreshDirty, 0);
        });

        // jQuery también avisa aquí de los envíos que hacen los listados con $(form).submit()
        $(document).on('submit', 'form', function (event) {
            var form = event.target;
            if (form.target && form.target !== '_self') {
                return;
            }

            var others = dirtyForms(form);
            if (others.length === 0 || form.dataset.dirtyConfirmed) {
                permitUnload();
                return;
            }

            event.preventDefault();
            animateSpinner('remove');
            confirmLosingChanges(others, function () {
                form.dataset.dirtyConfirmed = '1';
                permitUnload();
                animateSpinner('add');
                HTMLFormElement.prototype.submit.call(form);
            });
        });

        window.addEventListener('beforeunload', function (event) {
            if (!allowUnload && dirtyForms().length > 0) {
                event.preventDefault();
                event.returnValue = '';
            }
        });

        document.getElementById('blockDirtyDiscard').addEventListener('click', function () {
            dirtyForms().forEach(function (form) {
                form.reset();
            });
            setTimeout(refreshDirty, 0);
        });
        document.getElementById('blockDirtySave').addEventListener('click', function () {
            var forms = dirtyForms();
            if (forms.length === 1) {
                forms[0].requestSubmit();
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
        loadCounts();

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
