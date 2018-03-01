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
var accountGrid = null;
var accountDescription, accountBalance, unbalance;

/*
 * BEFORE CHANGE Funtions
 */
function bc_calculateUnbalance(changes) {
    // Calculate new unbalance
    var balance = Number(changes[3]) - Number(changes[2]);
    if (changes[1] === 'haber') {
        balance = balance * -1;
    }
    setUnbalance(balance);
}

function bc_loadAccountData(changes) {
    as_loadAccountData(changes[1], changes[3]);
}

/*
 * AFTER SELECTION Funtions
 */
function as_loadAccountData(colName, account, preventScrolling) {
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

/*
 * AUXILIAR Funtions
 */
function setAccountData(data) {
    // Update data labels
    accountDescription.innerText = data.description;
    accountBalance.innerText = data.balance;

    // Update graphic bars
    accountGraph.data.datasets.forEach((dataset) => {
        dataset.data.lenght = 0;
        dataset.data = Object.values(data.detail);
    });
    accountGraph.update();
}

function clearAccountData() {
    accountDescription.innerText = '';
    accountBalance.innerText = '';

    // Update graphic bars
    accountGraph.data.datasets.forEach((dataset) => {
        dataset.data.lenght = 0;
        dataset.data = [0,0,0,0,0,0,0,0,0,0,0,0];
    });
    accountGraph.update();
}

// Accumulate balance to unbalance
function setUnbalance(balance) {
    var amount = Number(unbalance.innerText) + Number(balance);
    unbalance.innerText = amount.toFixed(2);
}

// Calculate initial unbalance
function setInitialUnbalance() {
    // Calculate columns number for Debit & Credit
    var colDebit, colCredit, colField;
    for (var i = accountGrid.countCols()-1; i >= 0; i--) {
        colField = getGridColumnName(i);
        if (colField === 'debe' || colField === 'haber') {
            colDebit = (colField === 'debe') ? i : colDebit;
            colCredit = (colField === 'haber') ? i : colCredit;
            if (colDebit !== undefined && colCredit !== undefined) {
                break;
            }
        }
    }

    // Calculate unbalance
    var data = accountGrid.getData();
    var balance = 0.00;
    for (var i = 0, max = data.length; i < max; i++) {
        balance += Number(data[i][colDebit]) - Number(data[i][colCredit]);
    }
    setUnbalance(balance.toFixed(2));
}

/*
 * Document Ready. Create and configure Objects.
 */
$(document).ready(function () {
    // Init Working variables
    accountGrid = gridObject;                            // TODO: convert gridObject to POO
    accountDescription = document.getElementById('account-description');
    accountBalance = document.getElementById('account-balance');
    unbalance = document.getElementById('unbalance');

    // Calculate initial unbalance
    setInitialUnbalance();

    // Add control to balances columns
    addControlledColumn('debe');
    addControlledColumn('haber');
    controlledColumns['debe'].beforeChange = bc_calculateUnbalance;
    controlledColumns['haber'].beforeChange = bc_calculateUnbalance;

    // Add control events to Grid Controller
    controlledColumns['codsubcuenta'].beforeChange = bc_loadAccountData;
    controlledColumns['codsubcuenta'].afterSelection = as_loadAccountData;

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
