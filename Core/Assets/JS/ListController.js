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
var urls = {};

/**
 * Send a action to controller
 *
 * @param {string} actionValue
 */
function execActionForm(actionValue) {
    var form = document.getElementById('form' + tabActive);
    $('<input>').attr({type: 'hidden', name: 'action'}).appendTo(form);
    $('<input>').attr({type: 'hidden', name: 'code'}).appendTo(form);
    var lineCodes = getLineCodes();
    form.action.value = actionValue;
    form.code.value = lineCodes.join();
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
    return formData;
}

/**
 * Get list checked records
 *
 * @returns {Array}
 */
function getLineCodes() {
    var lineCodes = [];
    $('.tab' + tabActive + ' .listAction').each(function () {
        if ($(this).prop('checked')) {
            lineCodes.push($(this).val());
        }
    });
    return lineCodes;
}

/**
 * Exec export option into controller
 *
 * @param {string} option
 */
function goToExport(option) {
    $('#form' + tabActive).append('<input type="hidden" name="option" value="' + option + '"/>');
    execActionForm('export');
}

/**
 * Go to url target
 *
 * @param {string} url
 */
function goToOptions(url) {
    var previous = '';
    if (typeof url !== 'undefined') {
        previous = '&url=' + encodeURIComponent(url + '?active=' + tabActive);
    }
    window.location.href = 'EditPageOption?code=' + tabActive + previous;
}

/**
 * Send insert action to controller
 */
function insertRecord() {
    document.insertForm.action = urls[tabActive];
    document.insertForm.submit();
}

/**
 * Send filter data to controller
 *
 * @param {string} buttonID
 * @param {string} operator
 */
function setOperator(buttonID, operator) {
    document.getElementById(buttonID + '-operator').value = operator;
    document.getElementById(buttonID + '-btn').value = operator;
    $('#form' + tabActive).submit();
}

/**
 * Set order data to controller
 *
 * @param {string} value
 */
function setOrder(value) {
    $("#form" + tabActive + " :input[name='order']").val(value);
    $('#form' + tabActive).submit();
}

/**
 * Set disable status to insert button
 */
function setInsertStatus() {
    if (settings[tabActive].insert) {
        document.getElementById('b_new_record').classList.remove('disabled');
    } else {
        document.getElementById('b_new_record').classList.add('disabled');
    }
}

/*
 * Document Ready
 */
$(document).ready(function () {
    // set focus on tab change
    $('#mainTabs').on('shown.bs.tab', function (e) {
        tabActive = e.target.hash.substring(1);
        $('#form' + tabActive + ' :text:first').focus().select();
        setInsertStatus();
    });
    // set/unset all delete checkbox
    $('.listActionCB').click(function () {
        var checked = $(this).prop('checked');
        $('.listAction').prop('checked', checked);
    });
    // Update button insert status
    setInsertStatus();
});
