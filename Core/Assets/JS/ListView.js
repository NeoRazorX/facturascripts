/*!
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez  <carlos@facturascripts.com>
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

function listViewPrintAction(viewName, option) {
    $("#form" + viewName + " :input[name=\"action\"]").val('export');
    $("#form" + viewName).append('<input type="hidden" name="option" value="' + option + '"/>');
    $("#form" + viewName).submit();
    $("#form" + viewName + " :input[name=\"action\"]").val('');
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
    $("#form" + viewName + "Filters").toggle();
}

$(document).ready(function () {
    // disable enter key press
    $(".noEnterKey").keypress(function (e) {
        return !(e.which == 13 || e.keyCode == 13);
    });
});