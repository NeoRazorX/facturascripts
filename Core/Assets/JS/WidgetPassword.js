/*!
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
    $(document).on('click', '.edit-psw', function(){
        let input = $(this).parent().parent().find('input[type="text"]');

        if ($(input).hasClass('fs-psw')) {
            $(input).removeClass('fs-psw');
        } else {
            $(input).addClass('fs-psw');
        }
    });

    $(document).on('mouseenter mouseleave', '.list-psw', function(){
        let psw = $(this).parent().find('.pass');

        if ($(psw).hasClass('fs-psw')) {
            $(psw).removeClass('fs-psw');
        } else {
            $(psw).addClass('fs-psw');
        }
    });
});