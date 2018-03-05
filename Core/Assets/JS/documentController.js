/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

var documentLineData = [];
var documentUrl = "";
var hsTable = null;

function documentCalculate() {
    var data = {};
    $.each($("form[name=f_document_primary]").serializeArray(), function (key, value) {
        data[value.name] = value.value;
    });
    data.action = "calculate-document";
    data.lines = hsTable.getData();
    console.log(data);
    $.ajax({
        type: "POST",
        url: documentUrl,
        dataType: "text",
        data: data,
        success: function (results) {
            $("#doc_total").val(results);
        }
    });
}

function documentSave() {
    $("#btn-document-save").prop("disabled", true);

    var data = {};
    $.each($("form[name=f_document_primary]").serializeArray(), function (key, value) {
        data[value.name] = value.value;
    });
    data.action = "save-document";
    data.lines = hsTable.getData();
    console.log(data);
    $.ajax({
        type: "POST",
        url: documentUrl,
        dataType: "text",
        data: data,
        success: function (results) {
            if (results === "OK") {
                location.reload();
            } else if (results.substring(0, 4) === "NEW:") {
                location.href = results.substring(4);
            } else {
                alert(results);
            }
        }
    });

    $("#btn-document-save").prop("disabled", false);
}

function setAutocompletes(columns) {
    for (var key = 0; key < columns.length; key++) {
        if (columns[key].type === "autocomplete") {
            var source = columns[key].source["source"];
            var field = columns[key].source["fieldcode"];
            var title = columns[key].source["fieldtitle"];
            columns[key].source = function (query, process) {
                var ajaxData = {
                    term: query,
                    action: "autocomplete",
                    source: source,
                    field: field,
                    title: title
                };
                $.ajax({
                    type: "POST",
                    url: documentUrl,
                    dataType: "json",
                    data: ajaxData,
                    success: function (response) {
                        console.log("data", ajaxData);
                        console.log("response", response);
                        var values = [];
                        response.forEach(function (element) {
                            values.push(element.key);
                        });
                        process(values);
                    }
                });
            };
        }
    }

    return columns;
}

$(document).ready(function () {
    var container = document.getElementById("document-lines");
    hsTable = new Handsontable(container, {
        data: documentLineData.rows,
        columns: setAutocompletes(documentLineData.columns),
        rowHeaders: true,
        colHeaders: documentLineData.headers,
        stretchH: "all",
        autoWrapRow: true,
        manualRowResize: true,
        manualColumnResize: true,
        manualRowMove: true,
        manualColumnMove: false,
        contextMenu: true,
        filters: true,
        dropdownMenu: true,
        preventOverflow: "horizontal",
        minSpareRows: 1
    });

    Handsontable.hooks.add('afterChange', documentCalculate);
});
