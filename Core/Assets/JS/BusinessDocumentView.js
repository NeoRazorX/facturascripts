/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

var businessDocViewAutocompleteColumns = [];
var businessDocViewLineData = [];
var businessDocViewFormName = "f_document_primary";
var businessDocViewUrl = "";
var hsTable = null;

function beforeChange(changes, source) {
    // Check if the value has changed. Not Multiselection
    if (changes !== null && changes[0][2] !== changes[0][3]) {
        for (var i = 0; i < businessDocViewAutocompleteColumns.length; i++) {
            if (changes[0][1] === businessDocViewAutocompleteColumns[i]) {
                // apply for autocomplete columns
                if (typeof changes[0][3] === "string") {
                    changes[0][3] = changes[0][3].split(" | ", 1)[0];
                    var position = hsTable.getSelected();
                    hsTable.setDataAtCell(position[0][0], 2, '');
                }
            }
        }
    }
}

function businessDocViewAutocompleteGetData(formId, field, source, fieldcode, fieldtitle, term) {
    var formData = {};
    var rawForm = $("form[id=" + formId + "]").serializeArray();
    $.each(rawForm, function (i, input) {
        formData[input.name] = input.value;
    });
    formData["action"] = "autocomplete";
    formData["field"] = field;
    formData["source"] = source;
    formData["fieldcode"] = fieldcode;
    formData["fieldtitle"] = fieldtitle;
    formData["term"] = term;
    return formData;
}

function businessDocViewRecalculate() {
    var data = {};
    $.each($("#" + businessDocViewFormName).serializeArray(), function (key, value) {
        data[value.name] = value.value;
    });
    data.action = "recalculate-document";
    data.lines = getGridData();
    console.log("data", data);

    $.ajax({
        type: "POST",
        url: businessDocViewUrl,
        dataType: "json",
        data: data,
        success: function (results) {
            $("#doc_total").val(results.total);

            var rowPos = 0;
            results.lines.forEach(function (element) {
                var visualRow = hsTable.toVisualRow(rowPos);
                businessDocViewLineData.rows[visualRow] = element;
                rowPos++;
            });

            hsTable.render();
            console.log("results", results);
        },
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        }
    });
}

function businessDocViewSave() {
    $("#btn-document-save").prop("disabled", true);

    var data = {};
    $.each($("#" + businessDocViewFormName).serializeArray(), function (key, value) {
        data[value.name] = value.value;
    });
    data.action = "save-document";
    data.lines = getGridData();
    console.log(data);
    $.ajax({
        type: "POST",
        url: businessDocViewUrl,
        dataType: "text",
        data: data,
        success: function (results) {
            if (results.substring(0, 3) === "OK:") {
                location.href = results.substring(3);
            } else {
                alert(results);
            }
        }
    });

    $("#btn-document-save").prop("disabled", false);
}

function getGridData() {
    var rowIndex, lines = [];
    for (var i = 0, max = businessDocViewLineData.rows.length; i < max; i++) {
        rowIndex = hsTable.toVisualRow(i);
        if (hsTable.isEmptyRow(rowIndex)) {
            continue;
        }

        lines[rowIndex] = businessDocViewLineData.rows[i];
    }
    return lines;
}

function businessDocViewSetAutocompletes(columns) {
    for (var key = 0; key < columns.length; key++) {
        if (columns[key].type === "autocomplete") {
            businessDocViewAutocompleteColumns.push(columns[key].data);
            var source = columns[key].source["source"];
            var field = columns[key].source["fieldcode"];
            var title = columns[key].source["fieldtitle"];
            columns[key].source = function (query, process) {
                var ajaxData = {
                    term: query,
                    action: "autocomplete",
                    field: field,
                    source: source,
                    fieldcode: field,
                    fieldtitle: title
                };
                $.ajax({
                    type: "POST",
                    url: businessDocViewUrl,
                    dataType: "json",
                    data: ajaxData,
                    success: function (response) {
                        var values = [];
                        response.forEach(function (element) {
                            values.push(element.key + " | " + element.value);
                        });
                        process(values);
                    },
                    error: function (msg) {
                        alert(msg.status + " " + msg.responseText);
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
        data: businessDocViewLineData.rows,
        columns: businessDocViewSetAutocompletes(businessDocViewLineData.columns),
        rowHeaders: true,
        colHeaders: businessDocViewLineData.headers,
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
        minSpareRows: 5,
        enterMoves: {row: 0, col: 1},
        modifyColWidth: function (width, col) {
            if (width > 500) {
                return 500;
            }
        }
    });

    Handsontable.hooks.add("beforeChange", beforeChange);
    Handsontable.hooks.add("afterChange", businessDocViewRecalculate);

    $("#mainTabs li:first-child a").on('shown.bs.tab', function (e) {
        hsTable.render();
    });

    $("#doc_codserie").change(function () {
        businessDocViewRecalculate();
    });

    $(".autocomplete-dc").each(function () {
        var field = $(this).attr("data-field");
        var source = $(this).attr("data-source");
        var fieldcode = $(this).attr("data-fieldcode");
        var fieldtitle = $(this).attr("data-fieldtitle");
        var formName = $(this).closest("form").attr("name");
        $(this).autocomplete({
            source: function (request, response) {
                $.ajax({
                    method: "POST",
                    url: businessDocViewUrl,
                    data: businessDocViewAutocompleteGetData(formName, field, source, fieldcode, fieldtitle, request.term),
                    dataType: "json",
                    success: function (results) {
                        var values = [];
                        results.forEach(function (element) {
                            if (element.key !== null) {
                                values.push({key: element.key, value: element.key + " | " + element.value});
                            } else {
                                values.push({key: null, value: element.value});
                            }
                        });
                        response(values);
                    },
                    error: function (msg) {
                        alert(msg.status + " " + msg.responseText);
                    }
                });
            },
            select: function (event, ui) {
                var value = ui.item.value.split(" | ");
                if (value[0] !== null) {
                    $("#" + field + "Autocomplete").val(ui.item.key);
                    ui.item.value = value[1];
                }
            }
        });
    });
});
