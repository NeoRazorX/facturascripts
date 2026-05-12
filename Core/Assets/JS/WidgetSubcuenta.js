/*!
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

function widgetSubaccountDraw(id, results) {
    const rows = [];

    results.forEach(function (element) {
        const saldoValue = parseFloat(element.saldo || 0);
        const saldo = saldoValue.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        const saldoClass = saldoValue < 0 ? ' text-danger' : '';
        const codsubcuenta = widgetSubaccountDecodeHtml(element.codsubcuenta);
        const descripcion = widgetSubaccountDecodeHtml(element.descripcion || '');
        const $row = $('<tr/>', {
            class: 'clickableRow widget-subaccount-option'
        }).data('widget-subaccount-id', id)
            .data('widget-subaccount-value', codsubcuenta);

        const $link = $('<a/>', {
            class: 'widget-subaccount-link',
            href: element.url || '#',
            target: '_blank'
        }).append($('<i/>', {
            class: 'fa-solid fa-external-link-alt fa-fw'
        }));

        $row.append($('<td/>', {
            class: 'text-center'
        }).append($link));

        $row.append($('<td/>').append($('<b/>').text(codsubcuenta)));
        $row.append($('<td/>').text(descripcion));
        $row.append($('<td/>', {
            class: 'text-end' + saldoClass
        }).text(saldo));

        rows.push($row[0]);
    });

    $("#list_" + id).empty().append(rows);
}

function widgetSubaccountDecodeHtml(value) {
    if (value === null || value === undefined) {
        return '';
    }

    const textarea = document.createElement('textarea');
    textarea.innerHTML = String(value);
    return textarea.value;
}

function widgetSubaccountSearch(id) {
    $("#list_" + id).empty();
    const input = $("#" + id);
    const data = {
        action: 'widget-subcuenta-search',
        active_tab: input.closest('form').find('input[name="activetab"]').val(),
        col_name: input.attr("name"),
        query: $("#modal_" + id + "_q").val(),
        codejercicio: $("#modal_" + id + "_ej").val(),
        sort: $("#modal_" + id + "_s").val()
    };
    $.ajax({
        method: "POST",
        url: window.location.href,
        data: data,
        dataType: "json",
        success: function (results) {
            widgetSubaccountDraw(id, results);
        },
        error: function (msg) {
            alert(msg.status + " " + msg.responseText);
        }
    });
}

// Objeto para almacenar los timeouts de cada instancia del widget
let widgetSubaccountSearchTimeouts = {};

function widgetSubaccountSearchKp(id, event) {
    // Limpiar el timeout anterior si existe
    if (widgetSubaccountSearchTimeouts[id]) {
        clearTimeout(widgetSubaccountSearchTimeouts[id]);
    }

    // Crear un nuevo timeout para buscar después de 400ms
    widgetSubaccountSearchTimeouts[id] = setTimeout(function() {
        widgetSubaccountSearch(id);
    }, 400);
}

function widgetSubaccountSelect(id, value) {
    $("#" + id).val(value);
    $("#modal_" + id).modal("hide");
    $("#modal_span_" + id).text(value);
}

$(document).on('click', '.widget-subaccount-option', function () {
    const value = $(this).data('widget-subaccount-value');
    widgetSubaccountSelect(
        $(this).data('widget-subaccount-id'),
        (value === null || value === undefined) ? '' : String(value)
    );
});

$(document).on('click', '.widget-subaccount-link', function (event) {
    event.stopPropagation();
});
