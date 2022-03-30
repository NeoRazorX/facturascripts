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

export default class EditDocumentForm {
    /**
     * @param {string} formName purchasesForm, salesForm
     * @param {string} url URL del controlador al que se solcitara la accion
     */
    constructor(formName, url) {
        this.formName = formName;
        this.form = document.forms[formName];
        this.url = url;
    }

    /**
     * @param {string} action
     * @param {string} selectedLine
     */
    baseFormRequest(action, selectedLine) {
        animateSpinner('add');

        this.form['action'].value = action;
        this.form['selectedLine'].value = selectedLine;

        const formData = new FormData(this.form);
        const formDataObject = Object.fromEntries(formData.entries());
        const formDataJsonString = JSON.stringify(formDataObject);

        let data = new FormData();
        data.append('action', action);
        data.append('selectedLine', selectedLine);
        data.append('code', this.form['code'].value);
        data.append('multireqtoken', this.form['multireqtoken'].value);
        data.append('data', formDataJsonString);
        console.log('Data to send', data);

        return fetch(this.url, {
            method: 'POST',
            body: data
        }).then(function (response) {
            animateSpinner('remove', true);
            if (response.ok) {
                return response.json();
            }
            return Promise.reject(response);
        }).catch(function (error) {
            animateSpinner('remove', false);
            alert('error');
            console.warn(error);
        });
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
            });

        return false;
    }

    /**
     * @param {string} id
     * @param {float} total
     */
    baseLineTotalWithTaxes(id, total) {
        const iva = parseFloat(this.form['iva_' + id].value) || 0;
        const recargo = parseFloat(this.form['recargo_' + id].value) || 0;
        const irpf = parseFloat(this.form['irpf_' + id].value) || 0;
        const cantidad = parseFloat(this.form['cantidad_' + id].value) || 0;

        if (total <= 0) {
            return alert('total > 0');
        }

        if (cantidad <= 0) {
            return alert('cantidad > 0');
        }

        const pvp = (100 * total / cantidad) / (100 + iva + recargo - irpf);
        this.form['pvpunitario_' + id].value = Math.round(pvp * 100000) / 100000;
        this.baseFormAction('recalculate', '0');
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
            this.form[index].value = data.linesMap[index];
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
        if (this.form['action'].value === 'new-line') {
            $(".doc-line-desc:last").focus();
        } else if (this.form['action'].value === 'fast-line') {
            this.form['fastli'].focus();
        }
    }

    /**
     * @param {string} name purchasesFormLines, salesFormLines
     */
    sortableEnable(name) {
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
     * @param {string} name purchasesFormLines, salesFormLines
     */
    sortableDisable(name) {
        $(name).sortable("disable");
    }
}
