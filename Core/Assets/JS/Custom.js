/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

function animateSpinner(animation, result = null) {
    if (animation === 'add') {
        // añadimos la propiedad disabled al botón para evitar que se pueda pulsar varias veces
        $("button.btn-spin-action").attr('disabled', true);
        $("a.btn-spin-action").addClass('disabled').attr('aria-disabled', true);

        setToast('', 'spinner', '', 0);
        return;
    }

    if (animation === 'remove') {
        // eliminamos la propiedad disabled al botón para que se pueda pulsar de nuevo
        $("button.btn-spin-action").removeAttr('disabled');
        $("a.btn-spin-action").removeClass('disabled').attr('aria-disabled', false);

        // eliminamos el toast-spinner y toast-completed si existen
        $('#messages-toasts .toast-spinner, #messages-toasts .toast-completed').remove();

        // si result es null, terminamos
        if (result === null) {
            return;
        }

        if (result) {
            setToast('', 'completed', '', 3000);
            return;
        }

        setToast('', 'danger', '', 0);
    }
}

function confirmAction(viewName, action, title, message, cancel, confirm) {
    // Si ya existe un modal con el ID 'dynamicModal', lo eliminamos
    const existingModal = document.getElementById('dynamicConfirmActionModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Crear el HTML del modal como string usando los parámetros
    const modalHTML = `
    <div class="modal fade" id="dynamicConfirmActionModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="dynamicConfirmActionModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="dynamicConfirmActionModalLabel">${title}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            ${message}
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-spin-action" data-bs-dismiss="modal">${cancel}</button>
            <button type="button" id="saveDynamicConfirmActionModalBtn" class="btn btn-danger btn-spin-action">${confirm}</button>
          </div>
        </div>
      </div>
    </div>
  `;

    // Insertar el modal en el body
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Crear una instancia del modal y mostrarlo
    const myModal = new bootstrap.Modal(document.getElementById('dynamicConfirmActionModal'));
    myModal.show();

    // Añadir comportamiento al botón de "Guardar cambios"
    document.getElementById('saveDynamicConfirmActionModalBtn').addEventListener('click', function () {
        // Selecciona el formulario basado en viewName
        const form = document.getElementById("form" + viewName);

        // Asigna el valor del campo input[name="action"]
        const actionInput = form.querySelector('input[name="action"]');
        if (actionInput) {
            actionInput.value = action;
        }

        // Envía el formulario
        form.submit();

        // Cierra el modal
        myModal.hide();
    });

    // Eliminar el modal del DOM cuando se cierra
    document.getElementById('dynamicConfirmActionModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('dynamicConfirmActionModal').remove();
    });
}

function setModalParentForm(modal, form) {
    if (form.code) {
        // asignamos al formulario del modal el code del formulario donde sale el botón
        $("#" + modal).parent().find('input[name="code"]').val(form.code.value);
    } else if (form.elements['code[]']) {
        let codes = [];

        // recorremos los checkboxes del formulario donde sale el botón
        let checkboxes = document.querySelectorAll('input[name="code[]"]:checked');
        checkboxes.forEach((checkbox) => {
            codes.push(checkbox.value);
        });

        // asignamos al formulario del modal los checkboxes marcados del formulario donde sale el botón
        $("#" + modal).parent().find('input[name="code"]').val(codes.join());
        console.log(codes);
    }
}

$(document).ready(function () {
    $(".clickableRow").mousedown(function (event) {
        if (event.which === 1 || event.which === 2) {
            var href = $(this).attr("data-href");
            var target = $(this).attr("data-bs-target");
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
    $(document).on('shown.bs.modal', '.modal', function () {
        $(this).find('[autofocus]').focus();
    });
});