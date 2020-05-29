/*!
 * This file is part of FacturaScripts
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
    $(".calc-cost").change(function () {
        var coste = parseFloat($(this).val());
        var margen = parseFloat($(this.form.margen).val());
        if (margen > 0) {
            $(this.form.precio).val(coste * (100 + margen) / 100);
        }
    });
    $(".calc-margin").change(function () {
        var coste = parseFloat($(this.form.coste).val());
        var margen = parseFloat($(this).val());
        if (margen > 0) {
            $(this.form.precio).val(coste * (100 + margen) / 100);
        }
    });
    $(".calc-price").change(function () {
        $(this.form.margen).val(0);
    });
});