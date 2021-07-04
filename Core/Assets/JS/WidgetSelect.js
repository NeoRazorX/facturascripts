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

 $(document).ready(function () {

    $(".widget-action-select").each(function () {
        let $select = $(this);

        var data = {
            action: $(this).attr("data-action"),
            source: $(this).attr("data-source"),
            filter: $(this).attr("data-filter"),
            fieldcode: $(this).attr("data-fieldcode"),
            fieldtitle: $(this).attr("data-fieldtitle"),
        };

        /**
         * Para cada WidgetSelect con asyncsource, se asigna el evento onChange en el campo que filtra su orígen de datos
         */

        let $filter = $('[name="' + data.filter + '"]').on('change',function(){

            data.value = $(this).val();

            $.ajax({
                method: "POST",
                url: window.location.href,
                data: data,
                dataType: "json",
                success: function (results) {
                    $select.empty();
                    if(!$select.is(':required')){
                        $select.append($('<option>', {
                            value: null,
                            text: '------'
                        }));
                    }
                    results.forEach(function (element) {
                        $select.append($('<option>', {
                            value: element[data.fieldcode],
                            text: element[data.fieldtitle]
                        }));
                    });
                },
                error: function (msg) {
                    alert(msg.status + " " + msg.responseText);
                }
            });
        })

        if(!$select.find('option').length){
            $filter.change();
        }
    })

});
