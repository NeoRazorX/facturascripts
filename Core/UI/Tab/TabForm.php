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

namespace FacturaScripts\Core\UI\Tab;

use FacturaScripts\Core\Template\UI\SectionTab;

class TabForm extends SectionTab
{
    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->icon = 'fas fa-edit';
    }

    public function jsInitFunction(): string
    {
        return '';
    }

    public function jsRedrawFunction(): string
    {
        return '';
    }

    public function render(): string
    {
        return '<form>'
            . '<div class="container-fluid mt-4 mb-4">'
            . '<div class="form-row">'
            . '<div class="col">'
            . '<div class="form-group">'
            . '<label for="name">Name</label>'
            . '<input type="text" class="form-control" id="name" placeholder="Name">'
            . '</div>'
            . '</div>'
            . '<div class="col">'
            . '<div class="form-group">'
            . '<label for="surname">Surname</label>'
            . '<input type="text" class="form-control" id="surname" placeholder="Surname">'
            . '</div>'
            . '</div>'
            . '<div class="col-12">'
            . '<div class="form-group">'
            . '<label for="observation">Observation</label>'
            . '<textarea class="form-control" id="observation" rows="3"></textarea>'
            . '</div>'
            . '</div>'
            . '<div class="col-12 text-right">'
            . '<button type="button" class="btn btn-danger float-left">Delete</button>'
            . '<button type="reset" class="btn btn-secondary">Reset</button>'
            . '<button type="submit" class="btn btn-primary ml-1">Save</button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</form>';
    }
}