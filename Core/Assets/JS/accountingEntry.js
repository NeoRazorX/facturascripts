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

var documentUrl = "";
var accountGraph = null;

function loadAccountData(account) {
    if (account === null || account === '') {
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
        dataset.data = [];
    });
    accountGraph.update();
}

$(document).ready(function () {
    // Add control events to Grid Controller
    controlledColumns['codsubcuenta'].beforeChange = loadAccountData;
    controlledColumns['codsubcuenta'].afterSelection = loadAccountData;

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
