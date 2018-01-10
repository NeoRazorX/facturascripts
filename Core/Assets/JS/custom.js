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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

$(document).ready(function () {
    $(".datepicker").datepicker({
        dateFormat: "dd-mm-yy",
        firstDay: 1
    });

    // Adds a delay to help messages
    $('[data-toggle="popover"]').popover({
        delay: {"show": 1000, "hide": 100}
    });

    $(".clickableRow").mousedown(function (event) {
        if (event.which === 1) {
            var href = $(this).attr("data-href");
            var target = $(this).attr("data-target");
            if (typeof href !== typeof undefined && href !== false) {
                if (typeof target !== typeof undefined && target === "_blank") {
                    window.open($(this).attr("data-href"));
                } else {
                    parent.document.location = $(this).attr("data-href");
                }
            }
        }
    });

    $(".cancelClickable").mousedown(function (event) {
        event.preventDefault();
        event.stopPropagation();
    });
});