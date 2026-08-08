/*!
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez  <carlos@facturascripts.com>
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

function listFilterAutocompleteMsg(key) {
    return (typeof i18n !== 'undefined' && i18n[key]) ? i18n[key] : key;
}

function listFilterAutocompleteGetData(formId, formData, term) {
    var rawForm = $("form[id=" + formId + "]").serializeArray();
    $.each(rawForm, function (i, input) {
        formData[input.name] = input.value;
    });
    formData["action"] = "autocomplete";
    formData["term"] = term;
    return formData;
}

$(document).ready(function () {
    $(".filter-autocomplete").each(function () {
        var data = {
            field: $(this).attr("data-field"),
            fieldcode: $(this).attr("data-fieldcode"),
            fieldtitle: $(this).attr("data-fieldtitle"),
            name: $(this).attr("data-name"),
            source: $(this).attr("data-source")
        };
        var formId = $(this).closest("form").attr("id");
        $(this).autocomplete({
            source: function (request, response) {
                $.ajax({
                    method: "POST",
                    url: window.location.href,
                    data: listFilterAutocompleteGetData(formId, data, request.term),
                    dataType: "json",
                    success: function (results) {
                        try {
                            if (!Array.isArray(results)) {
                                throw new Error('response is not an array');
                            }
                            var values = [];
                            results.forEach(function (element) {
                                if (!element || element.key === undefined || element.value === undefined || element.value === null) {
                                    console.warn('filter-autocomplete: invalid element ignored', element);
                                    return;
                                }
                                if (element.key === null || element.key === element.value) {
                                    values.push(element);
                                } else {
                                    values.push({key: element.key, value: element.key + " | " + element.value});
                                }
                            });
                            response(values);
                        } catch (e) {
                            console.error('filter-autocomplete: invalid JSON response', e);
                            alert(listFilterAutocompleteMsg('autocomplete-error-invalid-response'));
                            response([]);
                        }
                    },
                    error: function (msg, textStatus, errorThrown) {
                        console.error('filter-autocomplete AJAX error | status:', msg.status, '| textStatus:', textStatus, '| errorThrown:', errorThrown);
                        console.error('filter-autocomplete responseText:', msg.responseText);
                        if (msg.status === 0) {
                            alert(listFilterAutocompleteMsg('autocomplete-error-network'));
                        } else if (msg.status === 400) {
                            alert(listFilterAutocompleteMsg('autocomplete-error-bad-request'));
                        } else if (msg.status >= 500) {
                            alert(listFilterAutocompleteMsg('autocomplete-error-server'));
                        } else {
                            alert(listFilterAutocompleteMsg('autocomplete-error-generic'));
                        }
                        response([]);
                    }
                });
            },
            select: function (event, ui) {
                $("form[id=" + formId + "] input[name=" + data.name + "]").val(ui.item.key);
                if (ui.item.key !== null) {
                    var value = ui.item.value.split(" | ");
                    if (value.length > 1) {
                        ui.item.value = value[1];
                    } else {
                        ui.item.value = value[0];
                    }
                }
                $(this).form().submit();
            }
        });
    });
});