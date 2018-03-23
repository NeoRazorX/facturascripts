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


var documentUrl = location.href;
var documentLineData = [];
var gridObject = null;               // TODO: convert to POO
var autocompleteColumns = [];
var eventManager = {};

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
        query = query.split(' - ', 1)[0];
        $.ajax({
            url: data.url,
            dataType: 'json',
            data: {
                term: query,
                action: 'autocomplete',
                source: source,
                field: field,
                title: title
            },
            success: function (response) {
                var values = [];
                response.forEach(function (element) {
                    values.push(element.key + " - " + element.value);
                });
                process(values);
            }
        });
    };
}

/* Configure source data for autocomplete columns */
function configureAutocompleteColumns(columns) {
    var column = null;
    var keys = Object.keys(columns);
    for (var i = 0, max = keys.length; i < max; i++) {
        column = columns[keys[i]];
        if (column['type'] === 'autocomplete') {
            // Add column to list of columns to control
            autocompleteColumns.push(column['data']);

            // assing calculate function to column
            column['source'] = assignSource(column['data-source']);
            delete column['data-source'];
        }
    }
}

/**
 * Return data structure.
 * You can indicate the name of the field where to save
 * the order index of the lines.
 *
 * @param {string} fieldOrder
 * @returns {Array}
 */
function getGridData(fieldOrder = null) {
    var rowIndex, lines = [];
    for (var i = 0, max = documentLineData.rows.length; i < max; i++) {
        rowIndex = gridObject.toVisualRow(i);
        if (gridObject.isEmptyRow(rowIndex)) {
            continue;
        }
        if (fieldOrder !== null) {
            documentLineData.rows[i][fieldOrder] = rowIndex;
        }
        lines.push(documentLineData.rows[i]);
    }
    return lines;
}

/* Return column value */
function getGridFieldData(row, fieldName) {
    var physicalRow = gridObject.toPhysicalRow(row);
    return documentLineData['rows'][physicalRow][fieldName];
}

/* Return row values */
function getGridRowValues(row) {
    var physicalRow = gridObject.toPhysicalRow(row);
    return documentLineData['rows'][physicalRow];
}

/* Set row value */
function setGridRowValues(row, values) {
    var physicalRow = gridObject.toPhysicalRow(row);
    for (var i = 0, max = values.length; i < max; i++) {
        documentLineData['rows'][physicalRow][values[i].field] = values[i].value;
    }
}

/* Return field name for a column */
function getGridColumnName(col) {
    var physicalColumn = gridObject.toPhysicalColumn(col);
    return documentLineData['columns'][physicalColumn]['data'];
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
    return selected.length > 0 ? gridObject.getSelected()[0][0] : null;
}

/* Return actual column selected */
function getColumnSelected() {
    var selected = gridObject.getSelected();
    return selected.length > 0 ? gridObject.getSelected()[0][1] : null;
}

/*
 * EVENT MANAGER
 */
function addEvent(name, fn) {
    switch (name) {
        case 'afterSelection':
        case 'beforeChange':
            eventManager[name] = fn;
            break;

        default:
            Handsontable.hooks.add(name, fn);
            break;
    }
}

function grid_afterSelection(row1, col1, row2, col2, preventScrolling) {
    // Check if editing
    var editor = gridObject.getActiveEditor();
    if (editor && editor.isOpened()) {
        return;
    }

    // Call to children event
    var events = Object.keys(eventManager);
    if (events.includes('afterSelection')) {
        eventManager['afterSelection'](row1, col1, row2, col2, preventScrolling);
    }
}

function grid_beforeChange(changes, source) {
    // Aply correction to autocomplete columns
    if (autocompleteColumns.length > 0) {
        for (var i = 0, max = changes.length; i < max; i++) {
            if (autocompleteColumns.includes(changes[i][1])) {
                changes[i][3] = changes[i][3].split(' - ', 1)[0];
            }
        }
    }

    // Call to children event
    var events = Object.keys(eventManager);
    if (events.includes('beforeChange')) {
        eventManager['beforeChange'](changes, source);
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
    var submitButton = document.getElementById('save-document');
    submitButton.disabled = true;
    try {
        var data = {
            action: "save-document",
            lines: getGridData('orden'),
            document: {}
        };

        var mainForm = $("form[name='" + mainFormName + "']");
        $.each(mainForm.serializeArray(), function(key, value) {
            switch (value.name) {
                case 'action':
                    break;

                case 'active':
                    data[value.name] = value.value;
                    break;

                default:
                    data.document[value.name] = value.value;
                    break;
            }
        });

        $.post(
            documentUrl,
            data,
            function (results) {
                if (results.error) {
                    alert(results.message);
                    return;
                }
                location.reload();
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
        configureAutocompleteColumns(documentLineData['columns']);

        // Create Grid Object
        gridObject = new Handsontable(container, {
            data: documentLineData.rows,
            columns: documentLineData.columns,
            rowHeaders: true,
            colHeaders: documentLineData.headers,
            stretchH: "all",
            autoWrapRow: false,
            contextMenu: false,
            manualRowResize: true,
            manualColumnResize: true,
            manualRowMove: true,
            manualColumnMove: false,
            minSpareRows: 1,
            minRows: 7
        });

        Handsontable.hooks.add('afterSelection', grid_afterSelection);
        Handsontable.hooks.add('beforeChange', grid_beforeChange);
    }
});