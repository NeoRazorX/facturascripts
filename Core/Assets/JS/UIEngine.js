/**
 * UIEngine.js — motor genérico HTML-over-the-wire del sistema de componentes UI.
 *
 * El servidor (PHP/Twig) es la única fuente de verdad del HTML: este motor solo
 * localiza, serializa, envía e intercambia fragmentos. Sin lógica de negocio.
 *
 * Atributos declarativos que emite el servidor:
 *   data-ui-form                    <form> interceptable; scope de serialización
 *   data-ui-on="click|change|input" trigger que dispara un evento al servidor
 *   data-ui-event="form:evento"     identificador que viaja en _ui_event
 *   data-ui-scope="none"            no serializar ningún form (eventos de página)
 *   data-ui-confirm="¿Seguro?"      confirm() previo
 *   data-ui-debounce="400"          debounce en ms para triggers input
 *   data-ui-behavior="nombre"       behavior a (re)inicializar tras cada swap
 *   data-ui-panel="nombre"          tab-content con persistencia de pestaña activa
 *
 * Envelope JSON de respuesta (UIResponse::toEnvelope):
 *   { protocol, ok, fragments: [{id, html, mode}], errors: {"form.campo": [msgs]},
 *     notices: [{level, message}], actions: [{type, ...}] }
 * Orden de aplicación: redirect → fragments → errors → notices → actions.
 *
 * API pública para plugins: window.UI.behaviors.register(name, {init(el)}),
 * window.UI.send(...), window.UI.initBehaviors(root).
 */
(function () {
    'use strict';

    // ------------------------------------------------------------------
    // csrf: token base + sufijo contador (MultiRequestProtection admite
    // incrementar la parte aleatoria en cliente; evita el rechazo por
    // token duplicado en envíos AJAX consecutivos)
    // ------------------------------------------------------------------
    var tokenCounter = 0;

    function nextToken(scopeEl) {
        var el = (scopeEl || document).querySelector('[name="multireqtoken"]')
            || document.querySelector('[name="multireqtoken"]');
        if (!el) return '';
        tokenCounter++;
        return el.value + 'n' + tokenCounter;
    }

    // ------------------------------------------------------------------
    // serializer
    // ------------------------------------------------------------------
    function serializeScope(formEl, fd) {
        if (!formEl) return;
        new FormData(formEl).forEach(function (value, key) {
            fd.append(key, value);
        });
    }

    // ------------------------------------------------------------------
    // transport: "última gana" por scope con AbortController
    // ------------------------------------------------------------------
    var inflight = new Map();

    function sendEvent(eventId, scopeEl, triggerEl, extraParams) {
        var fd = new FormData();
        fd.append('_ui_event', eventId);
        if (triggerEl && triggerEl.dataset.uiPath) {
            fd.append('_ui_source', triggerEl.dataset.uiPath);
        }
        Object.keys(extraParams || {}).forEach(function (key) {
            fd.append(key, extraParams[key]);
        });
        serializeScope(scopeEl, fd);
        // set() tras serializar: sustituye el token base del form por el token
        // con sufijo contador (un token por petición, anti-doble-submit del servidor)
        fd.set('multireqtoken', nextToken(scopeEl));

        var key = eventId.split(':')[0];
        var previous = inflight.get(key);
        if (previous) previous.abort();
        var controller = new AbortController();
        inflight.set(key, controller);

        startLoading(triggerEl, scopeEl);

        return fetch(window.location.href, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: fd,
            signal: controller.signal
        })
        .then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function (envelope) {
            applyEnvelope(envelope, scopeEl);
            return envelope;
        })
        .catch(function (err) {
            if (err.name === 'AbortError') return null;
            console.error('[UIEngine] fetch error', err);
            toast('error', (window.UI.texts.requestError || 'Error de red') + ': ' + err.message);
            return null;
        })
        .finally(function () {
            if (inflight.get(key) === controller) inflight.delete(key);
            stopLoading(triggerEl, scopeEl);
        });
    }

    // ------------------------------------------------------------------
    // loading
    // ------------------------------------------------------------------
    function startLoading(triggerEl, scopeEl) {
        if (triggerEl && triggerEl.tagName === 'BUTTON') {
            triggerEl.dataset.uiOriginalHtml = triggerEl.innerHTML;
            triggerEl.disabled = true;
            triggerEl.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        }
        if (scopeEl) scopeEl.setAttribute('aria-busy', 'true');
    }

    function stopLoading(triggerEl, scopeEl) {
        if (triggerEl && triggerEl.tagName === 'BUTTON' && triggerEl.isConnected) {
            triggerEl.disabled = false;
            if (triggerEl.dataset.uiOriginalHtml) {
                triggerEl.innerHTML = triggerEl.dataset.uiOriginalHtml;
                delete triggerEl.dataset.uiOriginalHtml;
            }
        }
        if (scopeEl && scopeEl.isConnected) scopeEl.removeAttribute('aria-busy');
    }

    // ------------------------------------------------------------------
    // applier
    // ------------------------------------------------------------------
    function resolveTarget(id) {
        return document.getElementById(id)
            || document.querySelector('[data-ui-path="' + id + '"]');
    }

    function captureFocus() {
        var el = document.activeElement;
        if (!el || !el.name) return null;
        return {
            name: el.name,
            start: typeof el.selectionStart === 'number' ? el.selectionStart : null,
            end: typeof el.selectionEnd === 'number' ? el.selectionEnd : null
        };
    }

    function restoreFocus(state) {
        if (!state) return;
        if (document.activeElement && document.activeElement.name === state.name) return;
        var el = document.querySelector('[name="' + CSS.escape(state.name) + '"]');
        if (!el) return;
        el.focus();
        if (state.start !== null && typeof el.setSelectionRange === 'function') {
            try { el.setSelectionRange(state.start, state.end); } catch (_) {}
        }
    }

    function swap(fragment) {
        var target = resolveTarget(fragment.id);
        if (!target) {
            console.warn('[UIEngine] fragment target not found:', fragment.id);
            return;
        }
        var focusState = captureFocus();

        if (fragment.mode === 'inner') {
            target.innerHTML = fragment.html;
            initBehaviors(target);
        } else if (fragment.mode === 'append') {
            var tpl = document.createElement('template');
            tpl.innerHTML = fragment.html;
            Array.prototype.slice.call(tpl.content.children).forEach(function (child) {
                target.appendChild(child);
                initBehaviors(child);
            });
        } else { // replace
            // preservar el estado activo de los tab-pane: el servidor siempre
            // renderiza la primera pestaña como activa, pero el usuario puede
            // estar viendo otra
            var wasActivePane = target.classList.contains('tab-pane') && target.classList.contains('active');
            var wasInactivePane = target.classList.contains('tab-pane') && !target.classList.contains('active');

            target.outerHTML = fragment.html;
            var replacement = resolveTarget(fragment.id);
            if (replacement) {
                if (wasActivePane) replacement.classList.add('show', 'active');
                if (wasInactivePane) replacement.classList.remove('show', 'active');
                initBehaviors(replacement);
            }
        }

        restoreFocus(focusState);
        document.dispatchEvent(new CustomEvent('ui:swapped', { detail: { id: fragment.id } }));
    }

    function applyErrors(errors) {
        var keys = Object.keys(errors || {});
        if (!keys.length) return;
        // los errores llegan ya renderizados dentro de los fragmentos; este mapa
        // solo sirve para llevar al usuario hasta el primer campo erróneo
        var first = keys[0].split('.'); // 'form.campo'
        var formEl = document.getElementById('ui-' + first[0]) || resolveTarget(first[0]);
        var input = formEl
            ? formEl.querySelector('[name="' + CSS.escape(first[0] + '[' + first[1] + ']') + '"]')
            : null;
        if (input) {
            input.scrollIntoView({ behavior: 'smooth', block: 'center' });
            input.focus();
        }
    }

    function toast(level, message) {
        var container = document.getElementById('ui-toasts');
        if (!container) {
            alert(message);
            return;
        }
        var color = level === 'error' || level === 'critical' ? 'danger'
            : level === 'warning' ? 'warning'
            : level === 'info' ? 'info'
            : 'success';
        var el = document.createElement('div');
        el.className = 'toast align-items-center text-bg-' + color + ' border-0';
        el.setAttribute('role', 'alert');
        el.innerHTML = '<div class="d-flex"><div class="toast-body"></div>'
            + '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';
        el.querySelector('.toast-body').innerHTML = message;
        container.appendChild(el);
        var toastObj = new bootstrap.Toast(el, { delay: 5000 });
        el.addEventListener('hidden.bs.toast', function () { el.remove(); });
        toastObj.show();
    }

    function applyActions(actions) {
        (actions || []).forEach(function (action) {
            var target = action.target ? resolveTarget(action.target) : null;
            switch (action.type) {
                case 'redirect':
                    window.location.assign(action.url);
                    break;
                case 'reload':
                    window.location.reload();
                    break;
                case 'focus':
                    if (target) {
                        var input = target.matches('input,select,textarea') ? target
                            : target.querySelector('input,select,textarea');
                        if (input) input.focus();
                    }
                    break;
                case 'scroll':
                    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    break;
                case 'tab':
                    if (target) {
                        var btn = document.querySelector('[data-bs-target="#' + target.id + '"]');
                        if (btn) new bootstrap.Tab(btn).show();
                    }
                    break;
                case 'modal':
                    if (target) {
                        var modalEl = target.matches('.modal') ? target : target.querySelector('.modal');
                        if (modalEl) {
                            var instance = bootstrap.Modal.getOrCreateInstance(modalEl);
                            action.action === 'hide' ? instance.hide() : instance.show();
                        }
                    }
                    break;
            }
        });
    }

    function applyEnvelope(envelope, scopeEl) {
        if (!envelope) return;

        var redirect = (envelope.actions || []).find(function (a) { return a.type === 'redirect'; });
        if (redirect) {
            window.location.assign(redirect.url);
            return;
        }

        (envelope.fragments || []).forEach(swap);
        applyErrors(envelope.errors);
        (envelope.notices || []).forEach(function (n) { toast(n.level, n.message); });
        applyActions((envelope.actions || []).filter(function (a) { return a.type !== 'redirect'; }));
    }

    // ------------------------------------------------------------------
    // behaviors: re-inicializables tras cada swap
    // ------------------------------------------------------------------
    var behaviors = {};

    function registerBehavior(name, def) {
        behaviors[name] = def;
    }

    function initBehaviors(root) {
        if (!root || !root.querySelectorAll) return;
        var nodes = Array.prototype.slice.call(root.querySelectorAll('[data-ui-behavior]'));
        if (root.matches && root.matches('[data-ui-behavior]')) nodes.unshift(root);

        nodes.forEach(function (el) {
            el.dataset.uiBehavior.split(/\s+/).forEach(function (name) {
                var def = behaviors[name];
                if (!def) return;
                var mark = 'uiInit' + name.replace(/[^a-z0-9]/gi, '');
                if (el.dataset[mark]) return;
                el.dataset[mark] = '1';
                def.init(el);
            });
        });
    }

    // ------------------------------------------------------------------
    // dispatcher: delegación a nivel document (sobrevive a los swaps)
    // ------------------------------------------------------------------
    function resolveScope(el) {
        if (el.dataset.uiScope === 'none') return null;
        if (el.dataset.uiScope && el.dataset.uiScope !== 'closest') {
            return document.querySelector(el.dataset.uiScope);
        }
        return el.closest('[data-ui-form]');
    }

    function trigger(el) {
        var eventId = el.dataset.uiEvent
            || (el.form && el.form.dataset ? el.form.dataset.uiEvent : null);
        if (!eventId) return;
        if (el.dataset.uiConfirm && !window.confirm(el.dataset.uiConfirm)) return;
        var extra = {};
        if (el.dataset.uiTargets) extra['_ui_targets'] = el.dataset.uiTargets;
        sendEvent(eventId, resolveScope(el), el, extra);
    }

    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-ui-on~="click"]');
        if (!el) return;
        e.preventDefault();
        trigger(el);
    });

    document.addEventListener('change', function (e) {
        var el = e.target.closest('[data-ui-on~="change"]');
        if (!el) return;
        trigger(el);
    });

    var debounceTimers = new WeakMap();
    document.addEventListener('input', function (e) {
        var el = e.target.closest('[data-ui-on~="input"]');
        if (!el) return;
        var delay = parseInt(el.dataset.uiDebounce || '400', 10);
        clearTimeout(debounceTimers.get(el));
        debounceTimers.set(el, setTimeout(function () { trigger(el); }, delay));
    });

    // submit del form: intercepta y envía por AJAX el evento del submitter
    // (o el submit por defecto del form). Sin JS el POST nativo sigue funcionando.
    document.addEventListener('submit', function (e) {
        var form = e.target.closest('form[data-ui-form]');
        if (!form) return;
        e.preventDefault();

        var submitter = e.submitter;
        var eventId = (submitter && submitter.value && submitter.name === '_ui_event')
            ? submitter.value
            : form.dataset.uiEvent;
        if (!eventId) return;
        if (submitter && submitter.dataset.uiConfirm && !window.confirm(submitter.dataset.uiConfirm)) return;
        sendEvent(eventId, form, submitter || form);
    });

    // los botones data-ui-on="click" dentro de forms son type=submit para la
    // degradación sin JS; con JS el listener de click ya los gestiona, así que
    // evitamos el doble disparo marcándolos gestionados en el listener de click
    // (preventDefault en click impide el submit nativo).

    // ------------------------------------------------------------------
    // behaviors integrados: select2 (estático y remoto)
    // ------------------------------------------------------------------
    function select2BaseOptions(el) {
        return {
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: el.dataset.uiPlaceholder || undefined,
            dropdownParent: window.jQuery(el.closest('.modal') || document.body)
        };
    }

    // select2 dispara el change de jQuery, no el nativo: lo re-emitimos para que
    // la delegación nativa del dispatcher (cascadas) funcione. e.originalEvent
    // evita el bucle cuando el change ya es nativo.
    function bridgeNativeChange(el) {
        window.jQuery(el).on('change', function (e) {
            if (e.originalEvent) return;
            el.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    registerBehavior('select2', {
        init: function (el) {
            if (!window.jQuery || !window.jQuery.fn.select2) return;
            window.jQuery(el).select2(select2BaseOptions(el));
            bridgeNativeChange(el);
        }
    });

    registerBehavior('select2-query', {
        init: function (el) {
            if (!window.jQuery || !window.jQuery.fn.select2) return;
            var options = select2BaseOptions(el);
            options.minimumInputLength = parseInt(el.dataset.uiQueryMin || '1', 10);
            options.ajax = {
                url: window.location.pathname,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    var data = {
                        _ui_query: 'search',
                        _ui_target: el.dataset.uiQueryTarget,
                        term: params.term || ''
                    };
                    // cascada remota: incluye el valor actual del campo padre
                    if (el.dataset.uiParentName && el.form) {
                        var parent = el.form.elements[el.dataset.uiParentName];
                        if (parent) data.parent = parent.value;
                    }
                    return data;
                }
            };
            if (el.dataset.uiTags) {
                options.tags = true;
            }
            window.jQuery(el).select2(options);
            bridgeNativeChange(el);
        }
    });

    // ------------------------------------------------------------------
    // behavior integrado: persistencia de pestaña activa
    // ------------------------------------------------------------------
    registerBehavior('tab-persist', {
        init: function (content) {
            var key = 'ui_tab_' + content.dataset.uiPanel;
            var savedId;
            try { savedId = sessionStorage.getItem(key); } catch (_) {}
            if (savedId && document.getElementById(savedId)) {
                var btn = document.querySelector('[data-bs-target="#' + savedId + '"]');
                if (btn) new bootstrap.Tab(btn).show();
            }
            content.addEventListener('shown.bs.tab', saveActive, true);
            document.querySelectorAll('[data-bs-target^="#' + content.id + '"]').forEach(function (b) {
                b.addEventListener('shown.bs.tab', saveActive);
            });

            function saveActive() {
                var active = content.querySelector('.tab-pane.active');
                if (active) {
                    try { sessionStorage.setItem(key, active.id); } catch (_) {}
                }
            }
        }
    });

    // ------------------------------------------------------------------
    // API pública + arranque
    // ------------------------------------------------------------------
    window.UI = {
        behaviors: { register: registerBehavior },
        initBehaviors: initBehaviors,
        send: sendEvent,
        toast: toast,
        texts: {}
    };

    document.addEventListener('DOMContentLoaded', function () {
        initBehaviors(document.body);
    });
})();
