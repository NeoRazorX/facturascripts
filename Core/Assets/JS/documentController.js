/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

var documentLineHeaders = [];
var documentLineColumns = [];
var documentLines = [];
var hsTable = null;
var msgConfirmDelete = "";
var msgAreYouSure = "";
var msgCancel = "";
var msgConfirm = "";
var tabActive = "";

function documentSave() {
    for(var num = 1; num < 200; num++) {
        var row = hsTable.getData(num);
    }
}

function deleteRecord(formName) {
    bootbox.confirm({
        title: msgConfirmDelete,
        message: msgAreYouSure,
        closeButton: false,
        buttons: {
            cancel: {
                label: "<i class='fa fa-times'></i> " + msgCancel
            },
            confirm: {
                label: "<i class='fa fa-check'></i> " + msgConfirm,
                className: "btn-danger"
            }
        },
        callback: function (result) {
            if (result) {
                var form = document.forms[formName];
                form.action.value = "delete";
                if (formName === "f_document_primary") {
                    form.action.value = "delete-doc";
                }
                form.submit();
            }
        }
    });
}

$(document).ready(function () {
    $("#mainTabs").on("shown.bs.tab", function (e) {
        tabActive = e.target.hash.substring(1);
    });
    var container = document.getElementById("document-lines");
    hsTable = new Handsontable(container, {
        data: documentLines,
        columns: documentLineColumns,
        rowHeaders: true,
        colHeaders: documentLineHeaders,
        stretchH: "all",
        autoWrapRow: true,
        manualRowResize: true,
        manualColumnResize: true,
        manualRowMove: true,
        manualColumnMove: true,
        contextMenu: true,
        filters: true,
        dropdownMenu: true,
        preventOverflow: "horizontal",
        minSpareRows: 1,
    });
});
