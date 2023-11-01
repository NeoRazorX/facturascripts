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
use FacturaScripts\Core\UI\Widget\WidgetNumber;
use FacturaScripts\Core\UI\Widget\WidgetText;
use FacturaScripts\Core\UI\Widget\WidgetTextarea;

class TabForm extends SectionTab
{
    /** @var array */
    protected $data;

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->icon = 'fas fa-edit';

        // añadimos algunos datos de prueba
        $this->data = [
            ['widget' => new WidgetText('name'), 'cols' => 4],
            ['widget' => new WidgetText('surname')],
            ['widget' => new WidgetNumber('age'), 'cols' => 2],
            ['widget' => new WidgetTextarea('observations'), 'cols' => 12]
        ];
    }

    public function jsInitFunction(): string
    {
        return '';
    }

    public function jsRedrawFunction(): string
    {
        return '';
    }

    public function render(string $context = ''): string
    {
        $html = '<form>'
            . '<div class="container-fluid mt-4 mb-4">'
            . '<div class="form-row">';

        foreach ($this->data as $row) {
            $html .= empty($row['cols']) ?
                '<div class="col-sm">' . $row['widget']->render() . '</div>' :
                '<div class="col-sm-' . $row['cols'] . '">' . $row['widget']->render() . '</div>';
        }

        $html .= '<div class="col-12 text-right">'
            . '<button type="button" class="btn btn-danger float-left">Delete</button>'
            . '<button type="reset" class="btn btn-secondary">Reset</button>'
            . '<button type="submit" class="btn btn-primary ml-1">Save</button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</form>';

        return $html;
    }
}