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

function disposeCifnifTooltip(input) {
    const tooltip = bootstrap.Tooltip.getInstance(input);
    if (tooltip !== null) {
        tooltip.dispose();
    }

    input.removeAttribute('aria-describedby');
}

function refreshCifnifWarning(input) {
    disposeCifnifTooltip(input);

    // solo mostramos el tooltip si el campo está visible: puede estar en otra pestaña
    const message = input.dataset.cifnifWarning;
    if (message === undefined || input.offsetParent === null) {
        return;
    }

    new bootstrap.Tooltip(input, {
        customClass: 'tooltip-warning tooltip-dismissible',
        placement: 'bottom',
        title: message,
        trigger: 'manual'
    }).show();
}

function showCifnifWarning(input, message) {
    input.dataset.cifnifWarning = message;
    input.classList.add('border-warning');
    refreshCifnifWarning(input);
}

function hideCifnifWarning(input) {
    delete input.dataset.cifnifWarning;
    input.classList.remove('border-warning');
    disposeCifnifTooltip(input);
}

function dismissCifnifWarning(input) {
    // quitamos el tooltip, pero el borde sigue indicando el duplicado hasta que cambie el cifnif
    delete input.dataset.cifnifWarning;
    disposeCifnifTooltip(input);
}

function checkDuplicatedCifnif(input) {
    const cifnif = input.value.trim();
    if (cifnif === '') {
        hideCifnifWarning(input);
        return;
    }

    const url = new URL(window.location.href);
    url.searchParams.set('action', 'check-cifnif');

    $.ajax({
        method: 'POST',
        url: url,
        data: {
            action: 'check-cifnif',
            cifnif: cifnif,
            code: input.form.code ? input.form.code.value : ''
        },
        dataType: 'json',
        success: function (data) {
            if (data.duplicated === true) {
                showCifnifWarning(input, data.message);
                return;
            }
            hideCifnifWarning(input);
        },
        error: function () {
            hideCifnifWarning(input);
        }
    });
}

function cifnifInputs() {
    return document.querySelectorAll('form[id^="formEdit"] input[name="cifnif"]');
}

$(document).ready(function () {
    // al cambiar de pestaña ocultamos el aviso, y lo volvemos a mostrar si el campo vuelve a estar visible
    $(document).on('hide.bs.tab', function () {
        cifnifInputs().forEach(disposeCifnifTooltip);
    }).on('shown.bs.tab', function () {
        cifnifInputs().forEach(refreshCifnifWarning);
    });

    // el usuario puede cerrar el aviso haciendo clic sobre él
    $(document).on('click', '.tooltip-dismissible', function () {
        cifnifInputs().forEach(function (input) {
            if (input.getAttribute('aria-describedby') === this.id) {
                dismissCifnifWarning(input);
            }
        }, this);
    });

    cifnifInputs().forEach(function (input) {
        // mientras escribe ocultamos el aviso anterior, y comprobamos al salir del campo
        $(input).on('input', function () {
            hideCifnifWarning(input);
        }).on('change', function () {
            checkDuplicatedCifnif(input);
        });

        // si el registro ya venía con un cifnif duplicado, avisamos al cargar
        if (input.value.trim() !== '') {
            checkDuplicatedCifnif(input);
        }
    });
});
