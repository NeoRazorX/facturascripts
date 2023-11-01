<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\UI\Widget;

use FacturaScripts\Core\Template\UI\Widget;

class WidgetFilemanager extends Widget
{
    public function render(string $context = ''): string
    {
        return '<div class="form-group">'
            . '<label for="' . $this->id() . '">' . $this->label('true') . '</label>'
            . '<button type="button" id="' . $this->id() . '" class="btn btn-secondary btn-block"'
            . ' data-toggle="modal" data-target="#modal_' . $this->id() . '">'
            . '<i class="fas fa-folder-open mr-1"></i> ' . $this->label(true) . '</button>'
            . '</div>'
            . $this->renderModal();
    }

    protected function renderModal(): string
    {
        return '<div class="modal fade" id="modal_' . $this->id() . '" tabindex="-1" aria-labelledby="modal_'
            . $this->id() . '_label" aria-hidden="true">'
            . '<div class="modal-dialog modal-dialog-centered modal-lg">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title" id="modal_' . $this->id() . '_label"><i class="fas fa-folder-open mr-1"></i> '
            . $this->label(true) . '</h5>'
            . '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
            . '<span aria-hidden="true">&times;</span>'
            . '</button>'
            . '</div>'
            . '<div class="modal-body">...</div>'
            . '<div class="modal-footer">'
            . '<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>'
            . '<button type="button" class="btn btn-primary">Save changes</button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }
}
