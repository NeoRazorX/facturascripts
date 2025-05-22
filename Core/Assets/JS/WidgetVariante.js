/*!
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
    let html = '';

    results.forEach(function (element) {
        html += '<tr class="clickableRow" onclick="widgetVarianteSelect(\'' + id + '\', \'' + element.match + '\');">'
            + '<td><b>' + element.referencia + '</b> ' + element.descripcion + '</td>'
            + '<td class="text-end text-nowrap">' + element.precio_str + '</td>'
            + '<td class="text-end text-nowrap">' + element.stock_str + '</td>'
            + '</tr>';
    });

    $("#list_" + id).html(html);
}

function widgetVarianteSearch(id) {
    $("#list_" + id).html("");

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

function widgetVarianteSearchKp(id, event) {
    if (event.key === "Enter") {
        event.preventDefault();
        widgetVarianteSearch(id);
    }
}

function widgetVarianteSelect(id, value) {
    $("#" + id).val(value);
    $("#modal_" + id).modal("hide");
    $("#modal_span_" + id).html(value);
}