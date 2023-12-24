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

var editListViewDeleteCancel = "";
var editListViewDeleteConfirm = "";
var editListViewDeleteMessage = "";
var editListViewDeleteTitle = "";

function editListViewDelete(viewName) {
  Fsbootbox.confirmAction(
    viewName,
    "delete",
    editListViewDeleteTitle,
    editListViewDeleteMessage
  );

  return false;
}

function editListViewSetOffset(viewName, value) {
  $("#form" + viewName + ' :input[name="action"]').val("");
  $("#form" + viewName + 'Offset :input[name="offset"]').val(value);
  $("#form" + viewName + "Offset").submit();
}

$(document).ready(function () {
  var formSelected = document.getElementById("EditListViewSelected");
  if (formSelected !== null) {
    formSelected.scrollIntoView();
  }
});
