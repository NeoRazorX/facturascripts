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
var accountDescription, accountBalance, unbalance, submitButton;

/*
 * EVENTS MANAGER Funtions
 */
function data_afterSelection(row1, col1, row2, col2, preventScrolling) {
    if (col1 === col2 && row1 === row2) {
        var fieldName = getGridColumnName(col1);
        if (fieldName === 'codsubcuenta') {
            var account = getGridFieldData(row1, fieldName);
            loadAccountData(account);
        }
    }
}

function data_beforeChange(changes, source) {
    if (changes === null) {
        return;
    }

    for (var i = 0, max = changes.length; i < max; i++) {
        if (changes[i][2] !== changes[i][3]) {
            switch (changes[i][1]) {
                case 'codsubcuenta':
                    var account = (max === 1) ? changes[0][3] : null;
                    loadAccountData(account);
                    break;

                case 'debe':
                case 'haber':
                    var balance = Number(changes[i][3]) - Number(changes[i][2]);
                    if (balance !== 0) {
                        if (changes[i][1] === 'haber') {
                            balance = balance * -1;
                        }
                        setUnbalance(balance);
                    }
                    break;
            }
        }
    }
}

/*
 * AUXILIAR Funtions
 */
/**
 * Load account data from server
 *
 * @param {string} account
 */
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

/**
 * Set subaccount data and graphic
 *
 * @param {json} data
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

/**
 * Clear data and graphic of subaccount
 */
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

/**
 * Accumulate balance to unbalance
 *
 * @param {number} balance
 */
function setUnbalance(balance) {
    var amount = Number(unbalance.innerText) + Number(balance);
    unbalance.innerText = amount.toFixed(2);
}

/**
 * Calculate unbalance from account entries
 */
function calculateEntryUnbalance() {
    var data = getGridData();
    var balance = 0.00;
    for (var i = 0, max = data.length; i < max; i++) {
        balance += Number(data[i].debe) - Number(data[i].haber);
    }
    setUnbalance(balance.toFixed(2));
}

/**
 * Save data to Database
 *
 * @returns {Boolean}
 */
function saveAccountEntry() {
    submitButton.prop("disabled", true);
    try {
        var mainForm = $("form[name^='EditAsiento-']");
        var data = {
            action: "save-document",
            lines: getGridData('orden'),
            document: {}
        };

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
        submitButton.prop("disabled", false);
        return false;
    }
}

/*
 * Document Ready. Create and configure Objects.
 */
$(document).ready(function () {
    if (document.getElementById("document-lines")) {
        // Init Working variables
        accountDescription = document.getElementById('account-description');
        accountBalance = document.getElementById('account-balance');
        unbalance = document.getElementById('unbalance');
        submitButton = $("button[id^='submit-EditAsiento-']");

        // Rewrite submit action
        submitButton.on('click', saveAccountEntry);

        // Calculate initial unbalance
        calculateEntryUnbalance();

        // Add control events to Grid Controller
        addEvent('beforeChange', data_beforeChange);
        addEvent('afterSelection', data_afterSelection);

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
    }
});
