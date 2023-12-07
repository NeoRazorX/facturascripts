/*!
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

var widgetLibrarySelectStr = "";

function widgetLibraryDraw(id, results) {
    let html = '';

    results.forEach(function (element) {
        let cssCard = '';
        if (element.id_file === element.selected_value) {
            cssCard = ' border-primary';
        }

        html += '<div class="col-6">'
            + '<div class="card ' + cssCard + ' shadow-sm mb-2">'
            + '<div class="card-body p-2">';

        let info = '<p class="card-text small">' + element.size + ', ' + element.date + ' ' + element.hour
            + '<a href="' + element.url + '" target="_blank" class="ml-2">'
            + '<i class="fa-solid fa-up-right-from-square"></i>'
            + '</a>'
            + '</p>';

        let js = "widgetLibrarySelect('" + id + "', '" + element.id_file + "');";

        if (element.is_image) {
            html += '<div class="media">'
                + '<img src="' + element.url + '" class="mr-3" alt="' + element.filename
                + '" width="64" type="button" onclick="' + js + '" title="' + widgetLibrarySelectStr + '">'
                + '<div class="media-body">'
                + '<h5 class="text-break mt-0">' + element.filename + '</h5>'
                + info
                + '</div>'
                + '</div>';
        } else {
            html += '<h5 class="card-title text-break mb-0" type="button" onclick="' + js + '" title="'
                + widgetLibrarySelectStr + '">' + element.filename + '</h5>' + info;
        }

        html += '</div>'
            + '</div>'
            + '</div>';
    });

    $("#list_" + id).html(html);
}

function widgetLibrarySearch(id) {
    $("#list_" + id).html("<div class='col-12 text-center pt-5 pb-5'><i class='fas fa-circle-notch fa-4x fa-spin'></i></div>");

    let input = $("#" + id);
    let data = {
        action: 'widget-library-search',
        active_tab: input.closest('form').find('input[name="activetab"]').val(),
        col_name: input.attr("name"),
        query: $("#modal_" + id + "_q").val(),
        sort: $("#modal_" + id + "_s").val(),
    };

    $.ajax({
        method: "POST",
        url: window.location.href,
        data: data,
        dataType: "json",
        success: function (results) {
            widgetLibraryDraw(id, results);
        },
        error: function (msg) {
            alert(msg.status + " " + msg.responseText);
        }
    });
}

function widgetLibrarySearchKp(id, event) {
    if (event.key === "Enter") {
        event.preventDefault();
        widgetLibrarySearch(id);
    }
}

function widgetLibrarySelect(id, id_file) {
    $("#" + id).val(id_file);
    $("#modal_" + id).modal("hide");
}

function widgetLibraryUpload(id, file) {
    let input = $("#" + id);

    let data = new FormData();
    data.append('action', 'widget-library-upload');
    data.append('active_tab', input.closest('form').find('input[name="activetab"]').val());
    data.append('col_name', input.attr("name"));
    data.append('file', file);

    $.ajax({
        method: "POST",
        url: window.location.href,
        data: data,
        dataType: "json",
        processData: false,
        contentType: false,
        success: function (results) {
            // si solamente hay un resultado, lo seleccionamos
            if (results.length === 1) {
                widgetLibrarySelect(id, results[0].id_file);
            } else {
                widgetLibraryDraw(id, results);
            }
        },
        error: function (msg) {
            alert(msg.status + " " + msg.responseText);
        }
    });
}
