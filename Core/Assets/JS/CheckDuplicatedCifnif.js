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

function showCifnifWarning(input, message) {
    let tooltip = bootstrap.Tooltip.getInstance(input);
    if (tooltip === null) {
        tooltip = new bootstrap.Tooltip(input, {
            customClass: 'tooltip-cifnif',
            placement: 'bottom',
            title: message,
            trigger: 'manual'
        });
    }

    tooltip.setContent({'.tooltip-inner': message});
    input.classList.add('border-warning');
    tooltip.show();
}

function hideCifnifWarning(input) {
    const tooltip = bootstrap.Tooltip.getInstance(input);
    if (tooltip !== null) {
        tooltip.hide();
    }

    input.classList.remove('border-warning');
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

$(document).ready(function () {
    $('form[id^="formEdit"] input[name="cifnif"]').each(function () {
        const input = this;

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
