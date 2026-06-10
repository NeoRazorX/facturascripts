/*!
 * This file is part of FacturaScripts
 * Copyright (C) 2020-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
        const coste = parseFloat($(this).val());
        const margen = parseFloat($(this.form.margen).val());
        if (margen > 0) {
            $(this.form.precio).val(coste * (100 + margen) / 100);
        }
    });
    $(".calc-margin").change(function () {
        const coste = parseFloat($(this.form.coste).val());
        const margen = parseFloat($(this).val());
        if (margen > 0) {
            $(this.form.precio).val(coste * (100 + margen) / 100);
        }
    });
    $(".calc-price").change(function () {
        $(this.form.margen).val(0);
    });

    $('#images-container').sortable({
        cursor: "move",
        tolerance: "pointer",
        opacity: 0.65,
        stop: (event, ui) => {

            const orden = Array.from(event.target.children).map(image => image.dataset.imageId);

            if (orden.length > 0) {

                const url = new URL(window.location.href);
                url.searchParams.append('action', 'sort-images');

                $.ajax({
                    method: "POST",
                    url,
                    data: {orden},
                    dataType: "json",
                    success: function (data) {
                        if (data.status !== 'ok') {
                            alert(data.message);
                        }
                    },
                    error: function (msg) {
                        alert(msg.status + " " + msg.responseText);
                    }
                });
            }
        },
    });
});
