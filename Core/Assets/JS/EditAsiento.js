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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/*
 * @author Artex Trading sa <jcuello@artextrading.com>
 */

var mainForm, accountDescription, accountBalance, total, unbalance, vatRegister;
var vatModal, vatForm;
var accountData = {'subaccount': ''};
var accountGraph = null;

/*
 * AMOUNT Functions Management
 */
/**
 * Accumulate balance to unbalance
 *
 * @param {number} balance
 */
function setUnbalance(balance) {
    var value = Number(balance);
    unbalance.textContent = value.toFixed(2);
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

/*
 * SUBACCOUNT Functions Management
 */
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
 * Set subaccount data and graphic
 *
 * @param {json} data
 */
function setAccountData(data) {
    // Save subAccount data calculate
    accountData.subaccount = data.subaccount;

    // Update data labels and buttons
    accountDescription.textContent = data.description;
    accountBalance.textContent = data.balance;
    vatRegister.disabled = (!data.codevat);

    // Update graphic bars
    accountGraph.data.datasets.forEach((dataset) => {
        dataset.data.lenght = 0;                       // Force delete old data
        dataset.data = Object.values(data.detail);
    });
    accountGraph.update();
}

/*
 * VAT REGISTER Funtions
 */
/**
 * Hide VAT Register form and save data into grid data
 *
 * @returns {Boolean}
 */
function saveVatRegister() {
    // cancel eventManager for submit form and hide form
    event.preventDefault();
    vatModal.modal('hide');

    // save form data into grid data
    var selectedRow = getRowSelected();
    if (selectedRow !== null) {
        var values = [
            {'field': 'documento', 'value': vatForm.find('.modal-body [name="documento"]').val()},
            {'field': 'cifnif', 'value': vatForm.find('.modal-body [name="cifnif"]').val()},
            {'field': 'baseimponible', 'value': vatForm.find('.modal-body [name="baseimponible"]').val()},
            {'field': 'iva', 'value': vatForm.find('.modal-body [name="iva"]').val()},
            {'field': 'recargo', 'value': vatForm.find('.modal-body [name="recargo"]').val()}
        ];
        setGridRowValues(selectedRow, values);
    }

    return false;
}

/**
 * Show VAT Register for account entry
 *
 * @param {string} mainForm
 * @param {string} action
 */
function showVatRegister(action, mainForm) {
    var selectedRow = getRowSelected();
    if (selectedRow !== null) {
        // Set form object, first time
        if (vatModal === undefined) {
            vatModal = $('#' + action);
            vatForm = vatModal.find('.modal-content form');
        }

        // Load data from documentLineData and master document to modal form
        var values = getGridRowValues(selectedRow);
        var docForm = vatForm.find('.modal-body [name="documento"]');
        docForm.val(values['documento']);
        vatForm.find('.modal-body [name="cifnif"]').val(values['cifnif']);
        vatForm.find('.modal-body [name="baseimponible"]').val(values['baseimponible']);
        vatForm.find('.modal-body [name="iva"]').val(values['iva']);
        vatForm.find('.modal-body [name="recargo"]').val(values['recargo']);

        // Redired submit action
        vatForm[0].onsubmit = saveVatRegister;

        // Show VAT modal form
        deselectCell();          // Force deselect grid data
        vatModal.modal('show');
        docForm.focus();
    }
}

/*
 * EVENTS MANAGER Funtions
 */
function customAfterSelection(row1, col1, row2, col2, preventScrolling) {
    if (col1 === col2 && row1 === row2) {
        var subAccount = getGridFieldData(row1, 'codsubcuenta');
        if (subAccount !== accountData.subaccount) {
            if (subAccount === null || subAccount === '') {
                clearAccountData();
                return;
            }

            var exercise = $('input[name=codejercicio]')[0];
            var data = {
                action: 'account-data',
                codsubcuenta: subAccount,
                codejercicio: exercise.value
            };
            $.ajax({
                type: "POST",
                url: documentUrl,
                dataType: "json",
                data: data,
                success: function (results) { setAccountData(results); },
                error: function (xhr, status, error) { clearAccountData(); }
            });
        }
    }
}

function customAfterChange(changes) {
    if (changes === null) {
        return;
    }

    var data = {
        action: "recalculate-document",
        changes: changes,
        lines: getGridData('order'),
        document: {}
    };
    $.each(mainForm.serializeArray(), function(key, value) {
        data.document[value.name] = value.value;
    });

    $.ajax({
        type: "POST",
        url: documentUrl,
        dataType: "json",
        data: data,
        success: function (results) {
            // update lines
            var rowPos = 0;
            results.lines.forEach(function (element) {
                var visualRow = gridObject.toVisualRow(rowPos);
                documentLineData.rows[visualRow] = element;
                rowPos++;
            });
            gridObject.render();

            // update subaccount data and graphic bars
            if (Object.keys(results.subaccount).length > 0) {
                setAccountData(results.subaccount);
            }

            // update ammounts
            setUnbalance(results.unbalance);
            total.val(results.total);

            // show VAT Register, if needed
            if (Object.keys(results.vat).length > 0) {
                showVatRegister('VAT-register', 'EditAsiento');
            }
        },
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        }
    });
}

/*
 * MAIN ENTRY Function
 */
/*
 * Document Ready. Create and configure Objects.
 */
$(document).ready(function () {
    if (document.getElementById("document-lines")) {
        // Init Working variables
        mainForm = $("form[name=EditAsiento]");
        accountDescription = document.getElementById('account-description');
        accountBalance = document.getElementById('account-balance');
        unbalance = document.getElementById('unbalance');
        total = $("form[name=EditAsiento] input[name=importe]");
        vatRegister = document.getElementById('vat-register-btn');
        vatRegister.disabled = true;

        // Set initial clone state
        if (getGridFieldData(0, 'idpartida') === undefined) {
            document.getElementById('clone-btn').disabled = true;
        }

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
                        label: 'Detalle por mes',
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

        selectCell(0,0);
    }
});
