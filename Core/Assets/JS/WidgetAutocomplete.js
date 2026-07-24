/*!
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

function widgetAutocompleteMsg(key) {
    return (typeof i18n !== 'undefined' && i18n[key]) ? i18n[key] : key;
}

function widgetAutocompleteGetData(formId, formData, term) {
    var rawForm = $("form[id=" + formId + "]").serializeArray();
    $.each(rawForm, function (i, input) {
        formData[input.name] = input.value;
    });
    formData["action"] = "autocomplete";
    formData["term"] = term;
    return formData;
}

$(document).ready(function () {
    $(".widget-autocomplete").each(function () {
        var data = {
            field: $(this).attr("data-field"),
            fieldcode: $(this).attr("data-fieldcode"),
            fieldfilter: $(this).attr("data-fieldfilter"),
            fieldtitle: $(this).attr("data-fieldtitle"),
            source: $(this).attr("data-source"),
            strict: $(this).attr("data-strict")
        };
        var formId = $(this).closest("form").attr("id");
        $(this).autocomplete({
            source: function (request, response) {
                $.ajax({
                    method: "POST",
                    url: window.location.href,
                    data: widgetAutocompleteGetData(formId, data, request.term),
                    dataType: "json",
                    success: function (results) {
                        try {
                            if (!Array.isArray(results)) {
                                throw new Error('response is not an array');
                            }
                            var values = [];
                            results.forEach(function (element) {
                                if (!element || element.key === undefined || element.value === undefined || element.value === null) {
                                    console.warn('widget-autocomplete: invalid element ignored', element);
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
                            console.error('widget-autocomplete: invalid JSON response', e);
                            alert(widgetAutocompleteMsg('autocomplete-error-invalid-response'));
                            response([]);
                        }
                    },
                    error: function (msg, textStatus, errorThrown) {
                        console.error('widget-autocomplete AJAX error | status:', msg.status, '| textStatus:', textStatus, '| errorThrown:', errorThrown);
                        console.error('widget-autocomplete responseText:', msg.responseText);
                        if (msg.status === 0) {
                            alert(widgetAutocompleteMsg('autocomplete-error-network'));
                        } else if (msg.status === 400) {
                            alert(widgetAutocompleteMsg('autocomplete-error-bad-request'));
                        } else if (msg.status >= 500) {
                            alert(widgetAutocompleteMsg('autocomplete-error-server'));
                        } else {
                            alert(widgetAutocompleteMsg('autocomplete-error-generic'));
                        }
                        response([]);
                    }
                });
            },
            select: function (event, ui) {
                if (ui.item.key !== null) {
                    $("form[id=" + formId + "] input[name=" + data.field + "]").val(ui.item.key);
                    var value = ui.item.value.split(" | ");
                    if (value.length > 1) {
                        ui.item.value = value[1];
                    } else {
                        ui.item.value = value[0];
                    }
                }
            },
            open: function (event, ui) {
                $(this).autocomplete('widget').css('z-index', 1500);
                return false;
            }
        });

        // cuando el modo estricto se encuentra deshabilitado
        // actualizamos el valor del input mientras escribe
        // por si el usuario no encuentra ningún item en el select
        // pueda pulsar tab o cambiar a otro input sin tener que
        // seleccionar ninguna opcion(ni si quiera la opción de su propia busqueda)
        $(this).on("keyup", function (event) {
            if(data.strict === "0" && event.key !== "Enter") {
                $("form[id=" + formId + "] input[name=" + data.field + "]").val(event.target.value);
            }
        });
    });
});
