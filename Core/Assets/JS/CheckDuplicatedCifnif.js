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

function checkDuplicatedCifnif(input, warning) {
    const cifnif = input.value.trim();
    if (cifnif === '') {
        warning.textContent = '';
        warning.classList.add('d-none');
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
                warning.textContent = data.message;
                warning.classList.remove('d-none');
                return;
            }
            warning.textContent = '';
            warning.classList.add('d-none');
        },
        error: function () {
            warning.textContent = '';
            warning.classList.add('d-none');
        }
    });
}

$(document).ready(function () {
    $('form[id^="formEdit"] input[name="cifnif"]').each(function () {
        const input = this;

        const warning = document.createElement('div');
        warning.className = 'small text-danger mt-1 d-none';
        input.parentNode.appendChild(warning);

        $(input).on('change', function () {
            checkDuplicatedCifnif(input, warning);
        });

        // si el registro ya venía con un cifnif duplicado, avisamos al cargar
        if (input.value.trim() !== '') {
            checkDuplicatedCifnif(input, warning);
        }
    });
});
