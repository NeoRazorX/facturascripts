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

function getGridData(row, fieldName) {
    return documentLineData['rows'][row][fieldName];
}

function getGridColumnName(col) {
    return documentLineData['columns'][col]['data'];
}

function setAccountData(data) {
    var accountData = JSON.parse(data);
    $('#account-description').text(accountData.description);
    $('#account-balance').text(accountData.balance);

    // Draw graphic bars
    var ctx = document.getElementById('detail-balance').getContext('2d');
    var chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'],
            datasets: [{
                label: 'Detail by months',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 1,
                fill: false,
                data: Object.values(accountData.detail)
            }]
        },
        options: {}
    });
}

function clearAccountData() {
    $('#account-description').text('');
}

function afterSelection(row1, col1, row2, col2, preventScrolling) {
    if (col1 === col2 && row1 === row2) {
        if (getGridColumnName(col1) === 'codsubcuenta') {
            var data = {
                action: 'account-data',
                code: getGridData(row1, 'idpartida')
            };
            $.get(documentUrl, data, setAccountData);
        }
    }
}

$(document).ready(function () {
    var container = document.getElementById("document-lines");
    hsTable = new Handsontable(container, {
        data: documentLineData.rows,
        columns: documentLineData.columns,
        rowHeaders: true,
        colHeaders: documentLineData.headers,
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
        minSpareRows: 1
    });

    Handsontable.hooks.add('afterSelection', afterSelection);
});
