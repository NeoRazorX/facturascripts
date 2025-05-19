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
function widgetLibrarySearch(id) {
    $("#list_" + id).html("<div class='col-12 text-center pt-5 pb-5'><i class='fa-solid fa-circle-notch fa-4x fa-spin'></i></div>");

    let input = $("div#" + id + ' input.input-hidden');
    let data = {
        action: 'widget-library-search',
        active_tab: input.closest('form').find('input[name="activetab"]').val(),
        col_name: input.attr("name"),
        widget_id: id,
        query: $("#modal_" + id + "_q").val(),
        sort: $("#modal_" + id + "_s").val(),
    };

    $.ajax({
        method: "POST",
        url: window.location.href,
        data: data,
        dataType: "json",
        success: function (results) {
            $('div#list_' + id).html(results.html);
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

function widgetLibrarySelect(id, id_file, filename) {
    $("div#" + id + ' input.input-hidden').val(id_file);
    $("div#" + id + ' span.file-name').text(filename);
    $('div#list_' + id + ' div.file').removeClass('border-primary');
    $('div#list_' + id + ' div[data-idfile="' + id_file + '"]').addClass('border-primary');
    $("#modal_" + id).modal("hide");
}

function widgetLibraryUpload(id, file) {
    let input = $("div#" + id + ' input.input-hidden');

    let data = new FormData();
    data.append('action', 'widget-library-upload');
    data.append('active_tab', input.closest('form').find('input[name="activetab"]').val());
    data.append('col_name', input.attr("name"));
    data.append('widget_id', id);
    data.append('file', file);

    $.ajax({
        method: "POST",
        url: window.location.href,
        data: data,
        dataType: "json",
        processData: false,
        contentType: false,
        success: function (results) {
            $('div#list_' + id).html(results.html);

            // si solamente hay un resultado, lo seleccionamos
            if (results.records === 1) {
                widgetLibrarySelect(id, results.new_file, results.new_filename);
            }
        },
        error: function (msg) {
            alert(msg.status + " " + msg.responseText);
        }
    });
}
