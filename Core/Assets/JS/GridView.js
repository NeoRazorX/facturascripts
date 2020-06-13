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

/*
 * @author Artex Trading sa <jcuello@artextrading.com>
 */

var documentUrl = location.href;
var documentLineData = [];
var documentReadOnly = false;
var gridObject = null;               // TODO: convert to POO
var autocompleteColumns = [];
var eventManager = {};
var cellSelected = {row: null, column: null};

/* Generate a single source function for autocomplete columns
 *
 * @param {Object} data
 * @returns {Function}
 */
function assignSource(data) {
    var source = data.source.slice(0);
    var field = data.field.slice(0);
    var title = data.title.slice(0);

    return function (query, process) {
        query = query.split(" | ", 1)[0];
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
            url: data.url,
            dataType: "json",
            data: ajaxData,
            success: function (response) {
                var values = [];
                response.forEach(function (element) {
                    values.push(element.key + " | " + element.value);
                });
                process(values);
            }
        });
    };
}

/**
 * Configure source data for autocomplete columns
 *
 * @param {Object} columns
 */
function configureAutocompleteColumns(columns) {
    var column = null;
    var keys = Object.keys(columns);
    for (var i = 0, max = keys.length; i < max; i++) {
        column = columns[keys[i]];
        if (column["type"] === "autocomplete") {
            // Add column to list of columns to control
            autocompleteColumns.push(column["data"]);

            // assing calculate function to column
            column["source"] = assignSource(column["data-source"]);
            delete column["data-source"];
        }
    }
}

/**
 * Return data structure.
 * You can indicate the name of the field where to save
 * the order index of the lines.
 *
 * @param {string} fieldOrder
 * @param {boolean} onlyWithData
 * @returns {Array}
 */
function getGridData(fieldOrder = null, onlyWithData = false) {
    var rowIndex, lines = [];
    for (var i = 0, max = documentLineData.rows.length; i < max; i++) {
        rowIndex = gridObject.toVisualRow(i);
        if (gridObject.isEmptyRow(rowIndex)) {
            continue;
        }
        if (fieldOrder !== null) {
            documentLineData.rows[i][fieldOrder] = rowIndex;
        }
        if (onlyWithData) {
            lines.push(documentLineData.rows[i]);
        } else {
            lines[rowIndex] = documentLineData.rows[i];
        }
    }
    return lines;
}

/* Return column value */
function getGridFieldData(row, fieldName) {
    var physicalRow = gridObject.toPhysicalRow(row);
    return documentLineData["rows"][physicalRow][fieldName];
}

/* Return row values */
function getGridRowValues(row) {
    var physicalRow = gridObject.toPhysicalRow(row);
    return documentLineData["rows"][physicalRow];
}

/* Set row value */
function setGridRowValues(row, values) {
    var physicalRow = gridObject.toPhysicalRow(row);
    for (var i = 0, max = values.length; i < max; i++) {
        documentLineData["rows"][physicalRow][values[i].field] = values[i].value;
    }
    gridObject.render();
}

/* Return field name for a column */
function getGridColumnName(col) {
    var physicalColumn = gridObject.toPhysicalColumn(col);
    return documentLineData["columns"][physicalColumn]["data"];
}

/* Select cell range */
function selectCell(row, col, endRow, endCol, scrollToCell, changeListener) {
    return gridObject.selectCell(row, col, endRow, endCol, scrollToCell, changeListener);
}

/* Deselect actual selected cell */
function deselectCell() {
    gridObject.deselectCell();
}

/* Return actual row selected */
function getRowSelected() {
    var selected = gridObject.getSelected();
    if (selected === undefined) {
        return cellSelected.row;
    }
    return selected.length > 0 ? gridObject.getSelected()[0][0] : null;
}

/* Return actual column selected */
function getColumnSelected() {
    var selected = gridObject.getSelected();
    if (selected === undefined) {
        return cellSelected.column;
    }
    return selected.length > 0 ? gridObject.getSelected()[0][1] : null;
}

/* Set Read Only to Grid View */
function setReadOnly(value) {
    gridObject.updateSettings({readOnly: value});
}

/*
 * EVENT MANAGER
 */
function addEvent(name, fn) {
    switch (name) {
        case "afterSelection":
        case "beforeChange":
            eventManager[name] = fn;
            break;

        default:
            Handsontable.hooks.add(name, fn);
            break;
    }
}

function eventAfterSelection(row1, col1, row2, col2, preventScrolling) {
    // Check if editing
    var editor = gridObject.getActiveEditor();
    if (editor && editor.isOpened()) {
        return;
    }

    // save selected cell
    cellSelected.row = row1;
    cellSelected.column = col1;

    // Call to children event
    var events = Object.keys(eventManager);
    if (events.includes("afterSelection")) {
        eventManager["afterSelection"](row1, col1, row2, col2, preventScrolling);
    }
}

function eventBeforeChange(changes, source) {
    // Aply correction to autocomplete columns
    var isAutoComplete = false;
    if (autocompleteColumns.length > 0) {
        for (var i = 0, max = changes.length; i < max; i++) {
            if (autocompleteColumns.includes(changes[i][1])) {
                var values = changes[i][3].split(" | ");
                changes[i][3] = values[0];
                isAutoComplete = (values.length > 1);
            }
        }
    }

    // Call to children event
    var events = Object.keys(eventManager);
    if (events.includes("beforeChange")) {
        eventManager["beforeChange"](changes, source, isAutoComplete);
    }
}

/*
 * User Interface Events
 */
/**
 * Save data to Database
 *
 * @param {string} mainFormName
 * @returns {Boolean}
 */
function saveDocument(mainFormName) {
    var submitButton = document.getElementById("save-document");
    submitButton.disabled = true;
    try {
        var data = {
            action: "save-document",
            lines: getGridData("sortnum", true),
            document: {}
        };
        var mainForm = $("#" + mainFormName);
        $.each(mainForm.serializeArray(), function (key, value) {
            data.document[value.name] = value.value;
        });
        $.ajax({
            type: "POST",
            url: documentUrl,
            dataType: "json",
            data: data,
            success: function (results) {
                if (results.error) {
                    alert(results.message);
                    return false;
                }
                location.assign(results.url);
            },
            error: function (xhr, status, error) {
                alert(xhr.responseText);
            }
        });
    } finally {
        submitButton.disabled = false;
        return false;
    }
}

/*
 * Document Ready. Create and configure Grid Object.
 */
$(document).ready(function () {
    // Grid Data
    var container = document.getElementById("document-lines");
    if (container) {
        // Prepare autocomplete columns
        configureAutocompleteColumns(documentLineData["columns"]);

        // Create Grid Object
        gridObject = new Handsontable(container, {
            readOnly: documentReadOnly,
            autoWrapRow: true,
            contextMenu: true,
            colHeaders: documentLineData.headers,
            columns: documentLineData.columns,
            colWidths: documentLineData.colwidths,
            data: documentLineData.rows,
            dropdownMenu: true,
            enterMoves: {row: 0, col: 1},
            filters: true,
            manualRowResize: true,
            manualColumnResize: true,
            manualRowMove: true,
            manualColumnMove: false,
            minSpareRows: 5,
            preventOverflow: "horizontal",
            rowHeaders: true,
            stretchH: 'all'
        });

        Handsontable.hooks.add("afterSelection", eventAfterSelection);
        Handsontable.hooks.add("beforeChange", eventBeforeChange);

        $("#mainTabs li:first-child a").on('shown.bs.tab', function (e) {
            gridObject.render();
        });
    }
});