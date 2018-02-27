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
var gridObject = null;               // TODO: convert to POO
var controlledColumns = {};

/* Add a column to controlate events */
function addControlledColumn(columnName) {
    controlledColumns[columnName] = {
        value: null,
        beforeChange: null,
        afterSelection: null
    };
}

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
                process(response);
            }
        });
    };
}

/* Configure source data for autocomplete columns */
function configureAutocompleteColumns(columns) {
    var column = null;
    var keys = Object.keys(columns);
    for (var i = 0; i < keys.length; i++) {
        column = columns[keys[i]];
        if (column['type'] === 'autocomplete') {
            // Add column to list of columns to control
            addControlledColumn(column['data']);

            // assing calculate function to column
            column['source'] = assignSource(column['data-source']);
            delete column['data-source'];
        }
    }
}

/* Return column value */
function getGridFieldData(row, fieldName) {
    return documentLineData['rows'][row][fieldName];
}

/* Return field name for a column */
function getGridColumnName(col) {
    return documentLineData['columns'][col]['data'];
}

/*
 * EVENT MANAGER
 */
function afterSelection(row1, col1, row2, col2, preventScrolling) {
    // Check if editing
    var editor = gridObject.getActiveEditor();
    if (editor && editor.isOpened()) {
        return;
    }

    // Not multiselection
    if (col1 === col2 && row1 === row2) {
        var column = null;
        var keys = Object.keys(controlledColumns);

        // propagate event to childrens if value has change
        for (var i = 0; i < keys.length; i++) {
            column = controlledColumns[keys[i]];
            var newValue = getGridFieldData(row1, keys[i]);
            if (newValue !== column.value && column.afterSelection !== null) {
                column.value = newValue;
                column.afterSelection(keys[i], newValue, preventScrolling);
            }
        }
    }
    return;
}

function beforeChange(changes, source) {
    // Check if the value has changed. Not Multiselection
    if (changes !== null && changes[0][2] !== changes[0][3]) {
        var column = null;
        var keys = Object.keys(controlledColumns);
        for (var i = 0; i < keys.length; i++) {
            if (changes[0][1] === keys[i]) {
                // aply for autocomplete columns
                if (typeof changes[0][3] === 'string') {
                    changes[0][3] = changes[0][3].split(' - ', 1)[0];
                }

                // propagate event to childrens
                column = controlledColumns[keys[i]];
                if (column.beforeChange !== null) {
                    column.beforeChange(changes[0]);
                }

                // Set new value
                column.value = changes[0][3];
            }
        }
    }
}

/*
 * Document Ready. Create and configure Grid Object.
 */
$(document).ready(function () {
    // Prepare autocomplete columns
    configureAutocompleteColumns(documentLineData['columns']);

    // Grid Data
    var container = document.getElementById("document-lines");
    if (container) {
        gridObject = new Handsontable(container, {
            data: documentLineData.rows,
            columns: documentLineData.columns,
            rowHeaders: true,
            colHeaders: documentLineData.headers,
            stretchH: "all",
            autoWrapRow: false,
            manualRowResize: true,
            manualColumnResize: true,
            manualRowMove: true,
            manualColumnMove: true,
            minSpareRows: 1,
            minRows: 6
        });

        Handsontable.hooks.add('afterSelection', afterSelection);
        Handsontable.hooks.add('beforeChange', beforeChange);
    }
});