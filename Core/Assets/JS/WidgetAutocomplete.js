/*!
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez  <carlos@facturascripts.com>
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

function widgetAutocompleteGetData(formId, field, source, fieldcode, fieldtitle, term) {
    var formData = {};
    var rawForm = $("form[id=" + formId + "]").serializeArray();
    $.each(rawForm, function (i, input) {
        formData[input.name] = input.value;
    });
    formData["action"] = "autocomplete";
    formData["field"] = field;
    formData["source"] = source;
    formData["fieldcode"] = fieldcode;
    formData["fieldtitle"] = fieldtitle;
    formData["term"] = term;
    return formData;
}

$(document).ready(function () {
    $(".widget-autocomplete").each(function () {
        var field = $(this).attr("data-field");
        var source = $(this).attr("data-source");
        var fieldcode = $(this).attr("data-fieldcode");
        var fieldtitle = $(this).attr("data-fieldtitle");
        var formId = $(this).closest("form").attr("id");
        $(this).autocomplete({
            source: function (request, response) {
                $.ajax({
                    method: "POST",
                    url: window.location.href,
                    data: widgetAutocompleteGetData(formId, field, source, fieldcode, fieldtitle, request.term),
                    dataType: "json",
                    success: function (results) {
                        var values = [];
                        results.forEach(function (element) {
                            if (element.key !== null) {
                                values.push({key: element.key, value: element.key + " | " + element.value});
                            } else {
                                values.push({key: null, value: element.value});
                            }
                        });
                        response(values);
                    },
                    error: function (msg) {
                        alert(msg.status + " " + msg.responseText);
                    }
                });
            },
            select: function (event, ui) {
                value = ui.item.value.split(" | ");
                if (value[0] !== null) {
                    $("form[id=" + formId + "] input[name=" + field + "]").val(ui.item.key);
                    ui.item.value = value[1];
                }
            }
        });
    });
});