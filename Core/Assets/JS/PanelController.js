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
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */

var tabActive = '';
var settings = {};
var url = '#';

/**
 * Send a action to controller
 *
 * @param {string} actionValue
 * @param {string} formName
 */
function execActionForm(actionValue, formName) {
    var form = document.forms[formName];
    form.action.value = actionValue;
    form.submit();
}

/**
 * Get data to configure autocomplete widget
 *
 * @param {string} formName
 * @param {string} source
 * @param {string} field
 * @param {string} title
 * @param {string} term
 * @returns {Object}
 */
function getAutocompleteData(formName, source, field, title, term) {
    var formData = {};
    var rawForm = $('form[name="' + formName + '"]').serializeArray();
    $.each(rawForm, function (i, input) {
        formData[input.name] = input.value;
    });
    formData['action'] = 'autocomplete';
    formData['source'] = source;
    formData['field'] = field;
    formData['title'] = title;
    formData['term'] = term;
    console.log(formData);
    return formData;
}

/**
 * Send insert action to controller
 */
function insertRecord() {
    document.insertForm.action = url;
    document.insertForm.submit();
}

/*
 * Document Ready
 */
$(document).ready(function () {
    // update tabActive on tab change
    $('#mainTabs').on('shown.bs.tab', function (e) {
        tabActive = e.target.hash.substring(1);
    });
});