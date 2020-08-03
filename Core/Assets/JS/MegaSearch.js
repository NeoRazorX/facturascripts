/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Returns an array of tr html tags from the given json.
 *
 * @param {type} json
 * @returns {Array|json2tr.items}
 */
function json2tr(json) {
    var items = [];
    $.each(json, function (key, val) {
        var tableTR = "";
        $.each(this, function (key2, val2) {
            if (key2 === "url") {
                tableTR += "";
            } else if (val2 === null) {
                tableTR += "<td>-</td>";
            } else if ($.isNumeric(val2) && val2 < 0) {
                tableTR += "<td class='text-danger'>" + val2 + "</td>";
            } else if (val2.length > 40) {
                tableTR += "<td>" + val2.substring(0, 40) + "...</td>";
            } else {
                tableTR += "<td>" + val2 + "</td>";
            }
        });
        items.push("<tr class='clickableRow' data-href='" + val.url + "'>" + tableTR + "</tr>");
    });

    return items;
}

/**
 * Asigns clickable events to new elements.
 */
function reloadClickableRow() {
    $(".clickableRow").mousedown(function (event) {
        if (event.which === 1) {
            var href = $(this).attr("data-href");
            var target = $(this).attr("data-target");
            if (typeof href !== typeof undefined && href !== false) {
                if (typeof target !== typeof undefined && target === "_blank") {
                    window.open($(this).attr("data-href"));
                } else {
                    parent.document.location = $(this).attr("data-href");
                }
            }
        }
    });
}

function searchOnSection(url) {
    $.getJSON(url, function (json) {
        $.each(json, function (key, val) {
            var items = json2tr(val.results);

            if (items.length > 0) {
                $("#v-pills-tab").append("<a class='nav-link' id='v-pills-" + key + "-tab' data-toggle='pill' href='#v-pills-"
                        + key + "' role='tab' aria-controls='v-pills-" + key + "' aria-expanded='true'>\n\
                    <span class='badge badge-secondary float-right'>" + items.length + "</span>\n\
                    <i class='" + val.icon + " fa-fw'></i>\n\
                    " + val.title + "\n\
                </a>");
                var tableHTML = "<thead><tr>";
                $.each(val.columns, function (key3, val3) {
                    tableHTML += "<th>" + val3 + "</th>";
                });
                tableHTML += "<tr></thead>";
                $.each(items, function (key3, val3) {
                    tableHTML += val3;
                });
                $("#v-pills-tabContent").append("<div class='tab-pane fade' id='v-pills-" + key + "' role='tabpanel' aria-labelledby='v-pills-" + key + "-tab'>\n\
                    <div class='card shadow'><div class='table-responsive'>\n\
                    <table class='table table-striped table-hover mb-0'>" + tableHTML + "</table>\n\
                    </div>\n\</div>\n\</div>");
                $("#v-pills-tab a:first").tab("show");
                reloadClickableRow();

                $("#no-data-msg").hide();
            }
        });
    });
}
