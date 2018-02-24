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
var accountEntries = null;
var accountGraph = null;
var calculateAccount = null;

function configureAutocompleteColumns(columns) {
    Object.keys(columns).forEach(function (key) {
        if (columns[key]['type'] === 'autocomplete') {
            var data = columns[key]['data-source'];
            columns[key]['source'] = function (query, process) {
                query = query.split(' - ', 1)[0];
                $.ajax({
                    url: data.url,
                    dataType: 'json',
                    data: {
                        term: query,
                        action: 'autocomplete',
                        source: data.source,
                        field: data.field,
                        title: data.title
                    },
                    success: function (response) {
                        process(response);
                    }
                });
            };
            delete columns[key]['data-source'];
        }
    });
}

function loadAccountData(account) {
    if (account === null) {
        clearAccountData();
        return;
    }

    var exercise = $('input[name=codejercicio]')[0];
    var data = {
        action: 'account-data',
        codsubcuenta: account,
        codejercicio: exercise.value
    };
    $.getJSON(documentUrl, data, setAccountData);
}

function setAccountData(data) {
    // Update data labels
    $('#account-description').text(data.description);
    $('#account-balance').text(data.balance);
    calculateAccount = data.subaccount;

    // Update graphic bars
    accountGraph.data.datasets.forEach((dataset) => {
        dataset.data.lenght = 0;
        dataset.data = Object.values(data.detail);
    });
    accountGraph.update();
}

function clearAccountData() {
    $('#account-description').text('');
    $('#account-balance').text('');
    calculateAccount = null;

    // Update graphic bars
    accountGraph.data.datasets.forEach((dataset) => {
        dataset.data.lenght = 0;
        dataset.data = [];
    });
    accountGraph.update();
}

function getGridFieldData(row, fieldName) {
    return documentLineData['rows'][row][fieldName];
}

function getGridColumnName(col) {
    return documentLineData['columns'][col]['data'];
}

function afterSelection(row1, col1, row2, col2, preventScrolling) {
    // Check if editing
    var editor = accountEntries.getActiveEditor();
    if (editor && editor.isOpened()) {
        return;
    }

    // Not multiselection
    if (col1 === col2 && row1 === row2) {
        var newAccount = getGridFieldData(row1, 'codsubcuenta');
        if (newAccount !== calculateAccount) {
            loadAccountData(newAccount);
        }
    }
    return;
}

function beforeChange(changes, source) {
    if (changes !== null) {
        if (changes[0][1] === 'codsubcuenta') {
            if (changes[0][2] !== changes[0][3]) {
                var value = changes[0][3];
                value = value.split(' - ', 1)[0];
                changes[0][3] = value;
                loadAccountData(value);
            }
        }
    }
}

$(document).ready(function () {
    // Grid Data
    var container = document.getElementById("document-lines");
    if (container) {
        accountEntries = new Handsontable(container, {
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

    // Graphic bars
    var ctx = document.getElementById('detail-balance');
    if (ctx) {
        ctx = ctx.getContext('2d');
        accountGraph = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'],
                datasets: [{
                    label: 'Detail by months',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgb(54, 162, 235)',
                    borderWidth: 1,
                    fill: false,
                    data: [0,0,0,0,0,0,0,0,0,0,0,0]
                }]
            },
            options: {}
        });
    }
});
