/*!
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

var editListViewDeleteCancel = "";
var editListViewDeleteConfirm = "";
var editListViewDeleteMessage = "";
var editListViewDeleteTitle = "";

function editListViewDelete(viewName) {
    // Si ya existe un modal con el ID 'dynamicEditListViewDeleteModal', lo eliminamos
    const existingModal = document.getElementById('dynamicEditListViewDeleteModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Crear el HTML del modal como string usando los parámetros
    const modalHTML = `
    <div class="modal fade" id="dynamicEditListViewDeleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="dynamicEditListViewDeleteModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="dynamicEditListViewDeleteModal">${editListViewDeleteTitle}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            ${editListViewDeleteMessage}
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-spin-action" data-bs-dismiss="modal">${editListViewDeleteCancel}</button>
            <button type="button" id="saveDynamicEditListViewDeleteModalBtn" class="btn btn-danger btn-spin-action">${editListViewDeleteConfirm}</button>
          </div>
        </div>
      </div>
    </div>
  `;

    // Insertar el modal en el body
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Crear una instancia del modal y mostrarlo
    const myModal = new bootstrap.Modal(document.getElementById('dynamicEditListViewDeleteModal'));
    myModal.show();

    // Añadir comportamiento al botón de "Guardar cambios"
    document.getElementById('saveDynamicEditListViewDeleteModalBtn').addEventListener('click', function () {
        // Ejecutar la acción de eliminar
        editListViewSetAction(viewName, "delete");

        // Cierra el modal
        myModal.hide();
    });

    // Eliminar el modal del DOM cuando se cierra
    document.getElementById('dynamicEditListViewDeleteModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('dynamicEditListViewDeleteModal').remove();
    });

    return false;
}

function editListViewSetAction(viewName, value) {
    $("#form" + viewName + " :input[name=\"action\"]").val(value);
    $("#form" + viewName).submit();
}

function editListViewSetOffset(viewName, value) {
    $("#form" + viewName + " :input[name=\"action\"]").val('');
    $("#form" + viewName + "Offset :input[name=\"offset\"]").val(value);
    $("#form" + viewName + "Offset").submit();
}

$(document).ready(function () {
    var formSelected = document.getElementById('EditListViewSelected');
    if (formSelected !== null) {
        formSelected.scrollIntoView();
    }
});