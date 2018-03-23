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

var accountDescription, accountBalance, unbalance, vatRegister;
var vatModal = null;
var accountGraph = null;
var accountData = {'subaccount': '', 'vat': [], 'row': null};

/*
 * EVENTS MANAGER Funtions
 */
function data_afterSelection(row1, col1, row2, col2, preventScrolling) {
    if (col1 === col2 && row1 === row2) {
        accountData.row = getRowSelected();
        var subAccount = getGridFieldData(row1, 'codsubcuenta');
        if (subAccount !== accountData.subaccount) {
            loadAccountData(subAccount, 'afterSelection');
        }
    }
}

function data_afterChange(changes, source) {
    if (changes === null) {
        return;
    }

    for (var i = 0, max = changes.length; i < max; i++) {
        if (changes[i][2] !== changes[i][3]) {
            switch (changes[i][1]) {
                case 'codsubcuenta':
                    var account = (max === 1) ? changes[0][3] : null;
                    loadAccountData(account, 'afterChange');
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

function data_beforeKeyDown(event) {
    if (event.keyCode === 13) {
    }
    if (event.keyCode === 9) {
    }
}

/*
 * AUXILIAR Funtions
 */
/**
 * Load account data from server
 *
 * @param {string} account
 * @param {string} source
 */
function loadAccountData(account, source) {
    if (account === null || account === '') {
        clearAccountData();
        return;
    }

    var exercise = $('input[name=codejercicio]')[0];
    var data = {
        source: source,
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
    // Save subAccount data
    accountData.subaccount = data.subaccount;
    accountData.vat = data.vat;

    // Update data labels
    accountDescription.textContent = data.description;
    accountBalance.textContent = data.balance;

    // Update graphic bars
    accountGraph.data.datasets.forEach((dataset) => {
        dataset.data.lenght = 0;
        dataset.data = Object.values(data.detail);
    });
    accountGraph.update();

    // Calculate VAT Process
    var hasVAT = (Object.keys(data.vat).length > 0);
    vatRegister.disabled = (hasVAT === false);
    if (data.source === 'afterChange' && hasVAT) {
        if (accountData.row !== null) {
            var values = [
                {'field': 'iva', 'value': accountData.vat.vat },
                {'field': 'recargo', 'value': accountData.vat.surcharge }
            ];
            setGridRowValues(accountData.row, values);      // Assign new VAT to data grid record
            showVATRegister(null, 'VAT-Register');
        }
    }
}

/**
 * Clear data and graphic of subaccount
 */
function clearAccountData() {
    // Clear subAccount data
    accountData.subaccount = '';
    accountData.vat = [];

    // Update data labels
    accountDescription.textContent = '';
    accountBalance.textContent = '';
    vatRegister.disabled = true;

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
    var amount = Number(unbalance.textContent) + Number(balance);
    unbalance.textContent = amount.toFixed(2);
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
 * Hide VAT Register form and save data into grid data
 *
 * @returns {Boolean}
 */
function saveVATRegister() {
    var vatForm = vatModal.find('.modal-content form');
    var values = [
        {'field': 'documento', 'value': vatForm.find('.modal-body [name="documento"]').val() },
        {'field': 'cifnif', 'value': vatForm.find('.modal-body [name="cifnif"]').val() },
        {'field': 'baseimponible', 'value': vatForm.find('.modal-body [name="baseimponible"]').val() },
        {'field': 'iva', 'value': vatForm.find('.modal-body [name="iva"]').val() },
        {'field': 'recargo', 'value': vatForm.find('.modal-body [name="recargo"]').val() }
    ];
    setGridRowValues(accountData.row, values);
    vatModal.modal('hide');
    return false;
}

/**
 * Show VAT Register for account entry
 *
 * @param {string} mainForm
 * @param {string} action
 */
function showVATRegister(mainForm, action) {
    if (accountData.row !== null) {
        // Set form object, first time
        if (vatModal === null) {
            vatModal = $('#' + action);
        }

        // Load data from documentLineData to modal form
        var values = getGridRowValues(accountData.row);
        var vatForm = vatModal.find('.modal-content form');
        var docForm = vatForm.find('.modal-body [name="documento"]');
        docForm.val(values['documento']);
        vatForm.find('.modal-body [name="cifnif"]').val(values['cifnif']);
        vatForm.find('.modal-body [name="baseimponible"]').val(values['baseimponible']);
        vatForm.find('.modal-body [name="iva"]').val(values['iva']);
        vatForm.find('.modal-body [name="recargo"]').val(values['recargo']);

        // Redired submit action
        vatForm[0].onsubmit = saveVATRegister;

        // Show VAT modal form
        deselectCell();          // Force deselect grid data
        vatModal.modal('show');
        docForm.focus();
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
        vatRegister = document.getElementById('vat-register-btn');
        vatRegister.disabled = true;

        // Calculate initial unbalance
        calculateEntryUnbalance();

        // Add control events to Grid Controller
        addEvent('afterChange', data_afterChange);
        addEvent('afterSelection', data_afterSelection);
        addEvent('beforeKeyDown', data_beforeKeyDown);

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
