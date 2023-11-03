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

namespace FacturaScripts\Core\UI;

use FacturaScripts\Core\Template\UI\Component;
use FacturaScripts\Core\Tools;

class Modal extends Component
{
    /** @var string */
    protected $title = '';

    public function render(string $context = ''): string
    {
        return '<div class="modal fade" id="' . $this->id() . '" tabindex="-1" aria-labelledby="' . $this->id()
            . '_label" aria-hidden="true">'
            . '<div class="modal-dialog">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title" id="' . $this->id() . '_label">' . $this->title . '</h5>'
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

    public function setTitle(string $title, array $params = []): self
    {
        $this->title = Tools::lang()->trans($title, $params);

        return $this;
    }

    public function title(): string
    {
        return $this->title;
    }
}
