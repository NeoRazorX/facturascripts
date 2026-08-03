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

function widgetVarianteDraw(id, results) {
    const rows = [];

    results.forEach(function (element) {
        let descripcion = widgetVarianteDecodeHtml(element.descripcion || '');
        if (descripcion.length > 300) {
            descripcion = descripcion.substring(0, 300) + '...';
        }

        // Determinar la clase de color para el precio
        let priceClass = '';
        if (element.precio < 0) {
            priceClass = ' text-danger';
        } else if (element.precio == 0) {
            priceClass = ' text-warning';
        }

        // Determinar la clase de color para el stock
        let stockClass = '';
        if (element.stock < 0) {
            stockClass = ' text-danger';
        } else if (element.stock == 0) {
            stockClass = ' text-warning';
        }

        const match = widgetVarianteDecodeHtml(element.match);
        const referencia = widgetVarianteDecodeHtml(element.referencia || '');
        const $row = $('<tr/>', {
            class: 'clickableRow widget-variante-option'
        }).data('widget-variante-id', id)
            .data('widget-variante-value', match);

        const $link = $('<a/>', {
            class: 'widget-variante-link',
            href: element.url || '#',
            target: '_blank'
        }).append($('<i/>', {
            class: 'fa-solid fa-external-link-alt fa-fw'
        }));

        $row.append($('<td/>', {
            class: 'text-center'
        }).append($link));

        $row.append($('<td/>')
            .append($('<b/>').text(referencia))
            .append(document.createTextNode(' ' + descripcion)));

        $row.append($('<td/>', {
            class: 'text-end text-nowrap' + priceClass
        }).text(element.precio_str || ''));

        $row.append($('<td/>', {
            class: 'text-end text-nowrap' + stockClass
        }).text(element.stock_str || ''));

        rows.push($row[0]);
    });

    $("#list_" + id).empty().append(rows);
}

function widgetVarianteDecodeHtml(value) {
    if (value === null || value === undefined) {
        return '';
    }

    const textarea = document.createElement('textarea');
    textarea.innerHTML = String(value);
    return textarea.value;
}

function widgetVarianteSearch(id) {
    $("#list_" + id).empty();

    let input = $("#" + id);
    let data = {
        action: 'widget-variante-search',
        active_tab: input.closest('form').find('input[name="activetab"]').val(),
        col_name: input.attr("name"),
        query: $("#modal_" + id + "_q").val(),
        codfabricante: $("#modal_" + id + "_fab").val(),
        codfamilia: $("#modal_" + id + "_fam").val(),
        sort: $("#modal_" + id + "_s").val(),
    };

    $.ajax({
        method: "POST",
        url: window.location.href,
        data: data,
        dataType: "json",
        success: function (results) {
            widgetVarianteDraw(id, results);
        },
        error: function (msg) {
            alert(msg.status + " " + msg.responseText);
        }
    });
}

// Objeto para almacenar los timeouts de cada instancia del widget
let widgetVarianteSearchTimeouts = {};

function widgetVarianteSearchKp(id, event) {
    // Limpiar el timeout anterior si existe
    if (widgetVarianteSearchTimeouts[id]) {
        clearTimeout(widgetVarianteSearchTimeouts[id]);
    }

    // Crear un nuevo timeout para buscar después de 400ms
    widgetVarianteSearchTimeouts[id] = setTimeout(function() {
        widgetVarianteSearch(id);
    }, 400);
}

function widgetVarianteSelect(id, value) {
    $("#" + id).val(value);
    $("#modal_" + id).modal("hide");
    $("#modal_span_" + id).text(value);
}

$(document).on('click', '.widget-variante-option', function () {
    const value = $(this).data('widget-variante-value');
    widgetVarianteSelect(
        $(this).data('widget-variante-id'),
        (value === null || value === undefined) ? '' : String(value)
    );
});

$(document).on('click', '.widget-variante-link', function (event) {
    event.stopPropagation();
});
