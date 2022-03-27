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

export default class DocumentForm {
    /**
     * @param {string} formName purchasesForm, salesForm
     * @param {string} url URL del controlador al que se solcitara la accion
     */
    constructor(formName, url) {
        this.formName = formName;
        this.documentForm = document.forms[formName];
        this.url = url;
    }

    /**
     * @param {string} action
     * @param {string} selectedLine
     */
    baseFormRequest(action, selectedLine) {
        animateSpinner('add');

        this.documentForm['action'].value = action;
        this.documentForm['selectedLine'].value = selectedLine;

        const formData = new FormData(this.documentForm);
        const formDataObject = Object.fromEntries(formData.entries());
        const formDataJsonString = JSON.stringify(formDataObject);

        let data = new FormData();
        data.append('action', action);
        data.append('code', this.documentForm['code'].value);
        data.append('multireqtoken', this.documentForm['multireqtoken'].value);
        data.append('selectedLine', selectedLine);
        data.append('data', formDataJsonString);
        console.log(data);

        const options = {
            method: 'POST',
            body: data
        };

        return fetch(this.url, options)
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
     */
    baseFormAction(action, selectedLine) {
        $('#headerModal').modal('hide');

        this.baseFormRequest(action, selectedLine)
            .then(data => {
                this.setHeaderHtml(data.header);
                this.setLinesHtml(data);
                this.setFooterHtml(data.footer);
                this.findProductList(data.products);
                this.showMessages(data.messages);
                this.setFocus();
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
     */
    baseLineTotalWithTaxes(id, total) {
        const iva = parseFloat(this.documentForm['iva_' + id].value) || 0;
        const recargo = parseFloat(this.documentForm['recargo_' + id].value) || 0;
        const irpf = parseFloat(this.documentForm['irpf_' + id].value) || 0;
        const cantidad = parseFloat(this.documentForm['cantidad_' + id].value) || 0;

        if (total <= 0) {
            alert('total > 0');
        } else if (cantidad <= 0) {
            alert('cantidad > 0');
        } else {
            const pvp = (100 * total / cantidad) / (100 + iva + recargo - irpf);
            this.documentForm['pvpunitario_' + id].value = Math.round(pvp * 100000) / 100000;
            this.baseFormAction('recalculate', '0');
        }
    }

    setHeaderHtml(header) {
        if (header !== '') {
            document.getElementById(this.formName + 'Header').innerHTML = header;
        }
    }

    setLinesHtml(data) {
        if (data.lines !== '') {
            document.getElementById(this.formName + 'Lines').innerHTML = data.lines;
            return;
        }

        for (const index in data.linesMap) {
            this.documentForm[index].value = data.linesMap[index];
        }
    }

    setFooterHtml(footer) {
        if (footer !== '') {
            document.getElementById(this.formName + 'Footer').innerHTML = footer;

        }
    }

    findProductList(products) {
        if (products !== '') {
            document.getElementById("findProductList").innerHTML = products;
        }
    }

    showMessages(messages) {
        if (Array.isArray(messages)) {
            messages.forEach(item => alert(item.message));
        }
    }

    setFocus() {
        if (this.documentForm['action'].value === 'new-line') {
            $(".doc-line-desc:last").focus();
        } else if (this.documentForm['action'].value === 'fast-line') {
            this.documentForm['fastli'].focus();
        }
    }
}
