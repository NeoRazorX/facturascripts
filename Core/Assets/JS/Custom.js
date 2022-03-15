/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

function confirmAction(viewName, action, title, message, cancel, confirm) {
    bootbox.confirm({
        title: title,
        message: message,
        closeButton: false,
        buttons: {
            cancel: {
                label: '<i class="fas fa-times"></i> ' + cancel
            },
            confirm: {
                label: '<i class="fas fa-check"></i> ' + confirm,
                className: "btn-warning"
            }
        },
        callback: function (result) {
            if (result) {
                $("#form" + viewName + " :input[name=\"action\"]").val(action);
                $("#form" + viewName).submit();
            }
        }
    });
}

function setModalParentForm(modal, form) {
    if (form.code) {
        // asignamos al formulario del modal el code del formulario donde sale el botón
        $("#" + modal).parent().find('input[name="code"]').val(form.code.value);
    } else if (form.elements['code[]']) {
        let codes = [];
        for (let num = 0; num < form.elements['code[]'].length; num++) {
            if (form.elements['code[]'][num].checked) {
                codes.push(form.elements['code[]'][num].value);
            }
        }
        // asignamos al formulario del modal los checkboxes marcados del formulario donde sale el botón
        $("#" + modal).parent().find('input[name="code"]').val(codes.join());
    }
}

$(document).ready(function () {
    $(".clickableRow").mousedown(function (event) {
        if (event.which === 1 || event.which === 2) {
            var href = $(this).attr("data-href");
            var target = $(this).attr("data-target");
            if (typeof href !== typeof undefined && href !== false) {
                if (typeof target !== typeof undefined && target === "_blank") {
                    window.open($(this).attr("data-href"));
                } else if (event.which === 2) {
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
    /* fix to dropdown submenus */
    $(document).on("click", "nav .dropdown-submenu", function (e) {
        e.stopPropagation();
    });
    $(document).on('shown.bs.modal', '.modal', function() {
        $(this).find('[autofocus]').focus();
    });
});