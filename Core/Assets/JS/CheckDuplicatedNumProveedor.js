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

function numProveedorInput() {
    const header = document.getElementById('purchasesFormHeader');
    return header === null ? null : header.querySelector('input[name="numproveedor"]');
}

function hideNumProveedorWarning() {
    // la cabecera se re-renderiza entera, así que puede quedar algún tooltip huérfano
    document.querySelectorAll('.tooltip-warning').forEach(function (tip) {
        tip.remove();
    });

    const input = numProveedorInput();
    if (input !== null) {
        const tooltip = bootstrap.Tooltip.getInstance(input);
        if (tooltip !== null) {
            tooltip.dispose();
        }
        input.classList.remove('border-warning');
    }
}

function showNumProveedorWarning(input, message) {
    const tooltip = new bootstrap.Tooltip(input, {
        customClass: 'tooltip-warning',
        placement: 'bottom',
        title: message,
        trigger: 'manual'
    });

    input.classList.add('border-warning');
    tooltip.show();
}

function checkDuplicatedNumProveedor() {
    hideNumProveedorWarning();

    const input = numProveedorInput();
    if (input === null || input.value.trim() === '') {
        return;
    }

    const form = document.forms['purchasesForm'];
    const codproveedor = form['codproveedor'] ? form['codproveedor'].value : '';
    if (codproveedor === '') {
        return;
    }

    const url = new URL(window.location.href);
    url.searchParams.set('action', 'check-numproveedor');

    $.ajax({
        method: 'POST',
        url: url,
        data: {
            action: 'check-numproveedor',
            code: form['code'] ? form['code'].value : '',
            codproveedor: codproveedor,
            numproveedor: input.value.trim()
        },
        dataType: 'json',
        success: function (data) {
            if (data.duplicated !== true) {
                return;
            }

            // la cabecera puede haberse re-renderizado mientras esperábamos
            const current = numProveedorInput();
            if (current !== null && current.value.trim() === input.value.trim()) {
                showNumProveedorWarning(current, data.message);
            }
        }
    });
}

$(document).ready(function () {
    // eventos delegados: el input se destruye en cada re-render de la cabecera
    $(document).on('input', '#purchasesFormHeader input[name="numproveedor"]', function () {
        hideNumProveedorWarning();
    }).on('change', '#purchasesFormHeader input[name="numproveedor"]', function () {
        checkDuplicatedNumProveedor();
    });

    checkDuplicatedNumProveedor();
});
