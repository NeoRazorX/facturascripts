/*!
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez  <carlos@facturascripts.com>
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

var listViewDeleteCancel = "";
var listViewDeleteConfirm = "";
var listViewDeleteMessage = "";
var listViewDeleteTitle = "";

function listViewCheckboxes(viewName) {
    var checked = $("#form" + viewName + " .listActionCB").prop("checked");
    $("#form" + viewName + " .listAction").each(function () {
        $(this).prop("checked", checked);
    });
}

function listViewDelete(viewName) {
    bootbox.confirm({
        title: listViewDeleteTitle,
        message: listViewDeleteMessage,
        closeButton: false,
        buttons: {
            cancel: {
                label: '<i class="fas fa-times"></i> ' + listViewDeleteCancel
            },
            confirm: {
                label: '<i class="fas fa-check"></i> ' + listViewDeleteConfirm,
                className: "btn-danger"
            }
        },
        callback: function (result) {
            if (result) {
                listViewSetAction(viewName, "delete");
            }
        }
    });

    return false;
}

function listViewOpenTab(viewName) {
    // buscamos todos los elementos con la clase toggle-ext-link
    $("#form" + viewName + " .toggle-ext-link").each(function () {
        // si tiene la clase d-none, la quitamos
        if ($(this).hasClass("d-none")) {
            $(this).removeClass("d-none");
        } else {
            // si no la tiene, la añadimos
            $(this).addClass("d-none");
        }
    });
}

function listViewPrintAction(viewName, option) {
    $("#form" + viewName).attr("target", "_blank");
    $("#form" + viewName + " :input[name=\"action\"]").val('export');
    $("#form" + viewName).append('<input type="hidden" name="option" value="' + option + '"/>');
    $("#form" + viewName).submit();
    $("#form" + viewName + " :input[name=\"action\"]").val('');
    $("#form" + viewName).attr("target", "");
}

function listViewSetAction(viewName, value) {
    $("#form" + viewName + " :input[name=\"action\"]").val(value);
    $("#form" + viewName).submit();
}

function listViewSetLoadFilter(viewName, value) {
    $("#form" + viewName + " :input[name=\"loadfilter\"]").val(value);
    $("#form" + viewName).submit();
}

function listViewSetOffset(viewName, value) {
    $("#form" + viewName + " :input[name=\"action\"]").val('');
    $("#form" + viewName + " :input[name=\"offset\"]").val(value);
    $("#form" + viewName).submit();
}

function listViewSetOrder(viewName, value) {
    $("#form" + viewName + " :input[name=\"action\"]").val('');
    $("#form" + viewName + " :input[name=\"order\"]").val(value);
    $("#form" + viewName).submit();
}

function listViewShowFilters(viewName) {
    $("#form" + viewName + "Filters").toggle(500);
}

$(document).ready(function () {
    $(".clickableListRow").mousedown(function (event) {
        if (event.which === 1 || event.which === 2) {
            var href = $(this).attr("data-href");
            var target = $(this).attr("data-target");
            if (typeof href !== typeof undefined && href !== false) {
                if (typeof target !== typeof undefined && target === "_blank") {
                    window.open($(this).attr("data-href"));
                } else if (event.which === 2) {
                    // buscamos todos los elementos con la clase toggle-ext-link
                    $(".toggle-ext-link").each(function () {
                        // si tiene la clase d-none, la quitamos
                        if ($(this).hasClass("d-none")) {
                            $(this).removeClass("d-none");
                        } else {
                            // si no la tiene, la añadimos
                            $(this).addClass("d-none");
                        }
                    });
                } else {
                    parent.document.location = $(this).attr("data-href");
                }
            }
        }
    });
    // disable enter key press
    $(".noEnterKey").keypress(function (e) {
        return !(e.which == 13 || e.keyCode == 13);
    });
});