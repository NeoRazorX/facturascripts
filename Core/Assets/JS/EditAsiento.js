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
var accountData = {'subaccount': '', 'vat': []};

/*
 * EVENTS MANAGER Funtions
 */
function customAfterSelection(row1, col1, row2, col2, preventScrolling) {
    if (col1 === col2 && row1 === row2) {
        var subAccount = getGridFieldData(row1, 'codsubcuenta');
        if (subAccount !== accountData.subaccount) {
            loadAccountData(subAccount, 'afterSelection');
        }
    }
}

function customAfterChange(changes, source) {
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

    if (data.source === 'afterChange') {
        var selectedRow = getRowSelected();
        if (selectedRow !== null) {
            setGridRowValues(selectedRow, valuesForNewAccount(hasVAT));      // Assign new VAT to data grid record
            if (hasVAT) {
                showVATRegister(null, 'VAT-Register');
            }
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
    var amount = getUnbalance() + Number(balance);
    unbalance.textContent = amount.toFixed(2);
}

/**
 * Get actual unbalance from accounting entries
 *
 * @returns {number}
 */
function getUnbalance() {
    return Number(unbalance.textContent);
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
 * Assign initial values to new accounting entry
 *
 * @param {boolean} hasVAT
 * @returns {Array}
 */
function valuesForNewAccount(hasVAT) {
    var result = [];
    var selectedRow = getRowSelected();
    var debit = 0.00;
    var credit = 0.00;

    if (selectedRow > 0) {
        // Calculate target for import column
        var unbalance = getUnbalance();
        if (unbalance < 0) {
            debit = (unbalance * -1);
        } else {
            credit = unbalance;
        }

        // Calculate values from before row
        var values = getGridRowValues(selectedRow - 1);
        var offsetting = values['codcontrapartida'];
        if (offsetting === accountData.subaccount) {
            offsetting = values['codsubcuenta'];
        }

        // Set initial values
        result.push({'field': 'concepto', 'value': values['concepto']});
        result.push({'field': 'codcontrapartida', 'value': offsetting });

        // Set VAT values
        if (hasVAT) {
            result.push({'field': 'iva', 'value': accountData.vat.vat });
            result.push({'field': 'recargo', 'value': accountData.vat.surcharge });
            result.push({'field': 'baseimponible', 'value': values['debe'] + values['haber']});
        };
    };

    result.push({ 'field': 'debe', 'value': debit });
    result.push({ 'field': 'haber', 'value': credit });
    return result;
}

/**
 * Hide VAT Register form and save data into grid data
 *
 * @returns {Boolean}
 */
function saveVATRegister() {
    var selectedRow = getRowSelected();
    var vatForm = vatModal.find('.modal-content form');
    var taxBase = vatForm.find('.modal-body [name="baseimponible"]').val();
    var pctVat = vatForm.find('.modal-body [name="iva"]').val();
    var pctSurcharge = vatForm.find('.modal-body [name="recargo"]').val();
    var taxVat = (taxBase * (pctVat / 100.00)) + (taxBase * (pctSurcharge / 100.00));
    var field = Number(getGridFieldData(selectedRow, 'debe')) > 0 ? 'haber' : 'debe';

    var values = [
        {'field': 'documento', 'value': vatForm.find('.modal-body [name="documento"]').val() },
        {'field': 'cifnif', 'value': vatForm.find('.modal-body [name="cifnif"]').val() },
        {'field': 'baseimponible', 'value': taxBase },
        {'field': 'iva', 'value': pctVat },
        {'field': 'recargo', 'value': pctSurcharge },
        {'field': 'debe', 'value': 0.00 },
        {'field': 'haber', 'value': 0.00 },
        {'field': field, 'value': taxVat }
    ];
    setGridRowValues(selectedRow, values);
    vatModal.modal('hide');
    selectCell(selectedRow + 1, 0, selectedRow + 1, 0, true);
    return false;              // cancel eventManager for submit form
}

/**
 * Show VAT Register for account entry
 *
 * @param {string} mainForm
 * @param {string} action
 */
function showVATRegister(mainForm, action) {
    var selectedRow = getRowSelected();
    if (selectedRow !== null) {
        // Set form object, first time
        if (vatModal === null) {
            vatModal = $('#' + action);
        }

        // Load data from documentLineData and master document to modal form
        var values = getGridRowValues(selectedRow);
        var vatForm = vatModal.find('.modal-content form');
        var document = $('.card-body input[name="documento"]').val();
        var docForm = vatForm.find('.modal-body [name="documento"]');
        docForm.val(document);
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
        addEvent('afterChange', customAfterChange);
        addEvent('afterSelection', customAfterSelection);

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
