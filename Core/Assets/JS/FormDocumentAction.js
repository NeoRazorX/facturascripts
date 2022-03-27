/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * @param {string} action
 * @param {string} selectedLine
 * @param {string} formName purchasesForm, salesForm
 * @param {string} controllerUrl URL del controlador al que se solcitara la accion
 */
export function baseFormRequest(action, selectedLine, formName, controllerUrl) {
    animateSpinner('add');

    document.forms[formName]['action'].value = action;
    document.forms[formName]['selectedLine'].value = selectedLine;

    const formData = new FormData(document.forms[formName]);
    const formDataObject = Object.fromEntries(formData.entries());
    const formDataJsonString = JSON.stringify(formDataObject);

    let data = new FormData();
    data.append('action', action);
    data.append('code', document.forms[formName]['code'].value);
    data.append('multireqtoken', document.forms[formName]['multireqtoken'].value);
    data.append('selectedLine', selectedLine);
    data.append('data', formDataJsonString);
    console.log(data);

    const options = {
        method: 'POST',
        body: data
    };

    return fetch(controllerUrl, options)
        .then(function (response) {
            animateSpinner('remove', true);
            if (response.ok) {
                return response.json();
            }
            return Promise.reject(response);
        }).catch(err => console.log(err));
}

/**
 * @param {string} action
 * @param {string} selectedLine
 * @param {string} formName purchasesForm, salesForm
 * @param {string} controllerUrl URL del controlador al que se solcitara la accion
 */
export function baseFormAction(action, selectedLine, formName, controllerUrl) {
    $('#headerModal').modal('hide');

    baseFormRequest(action, selectedLine, formName, controllerUrl)
        .then(function (data) {
            if (data.header !== '') {
                document.getElementById(formName + 'Header').innerHTML = data.header;
            }
            if (data.lines !== '') {
                document.getElementById(formName + 'Lines').innerHTML = data.lines;
            } else {
                $.each(data.linesMap, function (index, value) {
                    document.forms[formName][index].value = value;
                });
            }
            if (data.footer !== '') {
                document.getElementById(formName + 'Footer').innerHTML = data.footer;
            }
            if (data.products !== '') {
                document.getElementById("findProductList").innerHTML = data.products;
            }
            if (Array.isArray(data.messages)) {
                data.messages.forEach(item => alert(item.message));
            }
            if (document.forms[formName]['action'].value === 'new-line') {
                $(".doc-line-desc:last").focus();
            } else if (document.forms[formName]['action'].value === 'fast-line') {
                document.forms[formName]['fastli'].focus();
            }
        })
        .catch(function (error) {
            alert('error');
            console.warn(error);
            animateSpinner('remove', false);
        });

    return false;
}

/**
 * @param {string} id
 * @param {float} total
 * @param {string} formName purchasesForm, salesForm
 * @param {string} controllerUrl URL del controlador al que se solcitara la accion
 */
export function baseLineTotalWithTaxes(id, total, formName, controllerUrl) {
    const iva = parseFloat(document.forms[formName]['iva_' + id].value) || 0;
    const recargo = parseFloat(document.forms[formName]['recargo_' + id].value) || 0;
    const irpf = parseFloat(document.forms[formName]['irpf_' + id].value) || 0;
    const cantidad = parseFloat(document.forms[formName]['cantidad_' + id].value) || 0;

    if (total <= 0) {
        alert('total > 0');
    } else if (cantidad <= 0) {
        alert('cantidad > 0');
    } else {
        const pvp = (100 * total / cantidad) / (100 + iva + recargo - irpf);
        document.forms[formName]['pvpunitario_' + id].value = Math.round(pvp * 100000) / 100000;
        baseFormAction('recalculate', '0', formName, controllerUrl);
    }
}

/**
 * @param {string} name purchasesForm, salesForm
 */
export function sortableEnable(name) {
    const formLines = $(name);
    formLines.sortable({
        update: function (event, ui) {
            let orderInputs = $("input[name^='orden_']");
            let count = orderInputs.length * 10;
            orderInputs.each(function (index) {
                $(this).val(count - (index * 10));
            });
        }
    });
    formLines.sortable("option", "disabled", false);
    formLines.disableSelection();
}

/**
 * @param {string} name purchasesForm, salesForm
 */
export function sortableDisable(name) {
    $(name).sortable("disable");
}
