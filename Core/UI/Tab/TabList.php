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

class TabList extends SectionTab
{
    /** @var array */
    public $data = [];

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->icon = 'fas fa-list';

        // creamos algunos datos de ejemplo
        $columns = range(1, rand(3, 9));
        for ($i = 0; $i < rand(9, 49); $i++) {
            $row = [];
            for ($j = 0; $j < count($columns); $j++) {
                $row[] = 'Valor ' . rand(1, 100);
            }

            $this->data[] = $row;

            $this->counter++;
        }
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
        $html = '<div class="table-responsive">'
            . '<table class="table table-striped table-hover table-sm">'
            . '<thead>'
            . '<tr>';

        foreach (array_keys($this->data[0]) as $column) {
            $html .= '<th>Columna ' . $column . '</th>';
        }

        $html .= '</tr>'
            . '</thead>'
            . '<tbody>';

        foreach ($this->data as $row) {
            $html .= '<tr>';
            foreach ($row as $value) {
                $html .= '<td>' . $value . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody>'
            . '</table>'
            . '</div>';

        return $html;
    }
}