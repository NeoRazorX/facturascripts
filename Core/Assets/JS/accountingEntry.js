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

function getGridData(row, fieldName) {
    return documentLineData['rows'][row][fieldName];
}

function getGridColumnName(col) {
    return documentLineData['columns'][col]['data'];
}

function setAccountData(data) {
    console.log('setAccountData');
    console.log(typeof data);
    console.log(data);

    // Update data labels
    $('#account-description').text(data.description);
    $('#account-balance').text(data.balance);

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

    // Update graphic bars
    accountGraph.data.datasets.forEach((dataset) => {
        dataset.data.lenght = 0;
    });
    accountGraph.update();
}

function afterSelection(row1, col1, row2, col2, preventScrolling) {
    console.log('afterSelection');
    if (col1 === col2 && row1 === row2) {
        console.log('afterSelection 2');
        if (getGridColumnName(col1) === 'codsubcuenta') {
            console.log('afterSelection 3');
            var exercise = $('input[name=codejercicio]')[0];
            var data = {
                action: 'account-data',
                codsubcuenta: getGridData(row1, 'codsubcuenta'),
                codejercicio: exercise.value
            };
            console.log('getJSON');
            console.log(data);
            $.getJSON(documentUrl, data, setAccountData);
        }
    }
}

function afterChange(changes, source) {
    console.log(changes);
    console.log(source);
}

$(document).ready(function () {
    // Grid Data
    var container = document.getElementById("document-lines");
    accountEntries = new Handsontable(container, {
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
//    Handsontable.hooks.add('afterChange', afterChange);

    // Graphic bars
    var ctx = document.getElementById('detail-balance').getContext('2d');
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
});
