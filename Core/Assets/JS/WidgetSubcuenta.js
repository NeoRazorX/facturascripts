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
        const match = widgetSubaccountDecodeHtml(element.match);
        const $row = $('<tr/>', {
            class: 'clickableRow widget-subaccount-option'
        }).data('widget-subaccount-id', id)
            .data('widget-subaccount-value', match);

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

// Objeto para almacenar la búsqueda en curso de cada instancia del widget
let widgetSubaccountSearchRequests = {};

function widgetSubaccountSearch(id, selected) {
    $("#list_" + id).empty().append(
        $('<tr/>').append($('<td/>', {
            class: 'text-center',
            colspan: 4
        }).append($('<div/>', {
            class: 'spinner-border spinner-border-sm text-primary',
            role: 'status'
        })))
    );

    const input = $("#" + id);
    const data = {
        action: 'widget-subcuenta-search',
        active_tab: input.closest('form').find('input[name="activetab"]').val(),
        col_name: input.attr("name"),
        query: $("#modal_" + id + "_q").val(),
        codejercicio: $("#modal_" + id + "_ej").val(),
        sort: $("#modal_" + id + "_s").val()
    };

    // en la carga inicial enviamos el valor actual para mostrarlo el primero
    if (selected) {
        data.selected = selected;
    }

    // cancelamos la búsqueda anterior para que no sobrescriba los resultados nuevos
    if (widgetSubaccountSearchRequests[id]) {
        widgetSubaccountSearchRequests[id].abort();
    }

    widgetSubaccountSearchRequests[id] = $.ajax({
        method: "POST",
        url: window.location.href,
        data: data,
        dataType: "json",
        success: function (results) {
            widgetSubaccountDraw(id, results);
        },
        error: function (msg, status) {
            if (status === 'abort') {
                return;
            }
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

// cargamos la lista la primera vez que se abre cada modal
$(document).on('show.bs.modal', '.widget-subaccount-modal', function () {
    const $modal = $(this);
    if ($modal.data('widget-subaccount-loaded')) {
        return;
    }

    $modal.data('widget-subaccount-loaded', true);
    const id = $modal.data('widget-subaccount-id');
    widgetSubaccountSearch(id, $("#" + id).val());
});

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
