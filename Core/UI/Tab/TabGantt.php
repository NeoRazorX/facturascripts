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
use FacturaScripts\Dinamic\Lib\AssetManager;
use Symfony\Component\HttpFoundation\Request;

class TabGantt extends SectionTab
{
    /** @var array */
    public $data = [];

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->icon = 'fa-solid fa-chart-gantt';

        AssetManager::add('css', 'https://cdnjs.cloudflare.com/ajax/libs/frappe-gantt/0.6.1/frappe-gantt.min.css');
        AssetManager::add('js', 'https://cdnjs.cloudflare.com/ajax/libs/frappe-gantt/0.6.1/frappe-gantt.min.js');

        // creamos algunos datos de ejemplo
        foreach (range(1, rand(3, 10)) as $i) {
            $start = date('Y-m-d', strtotime('+' . $i . ' days'));
            $end = date('Y-m-d', strtotime('+' . ($i + rand(1, 10)) . ' days'));

            $this->data[] = [
                'id' => 'Task ' . $i,
                'name' => 'Redesign website ' . $i,
                'start' => $start,
                'end' => $end,
                'progress' => rand(0, 100),
                'dependencies' => rand(0, 3) ? '' : 'Task ' . rand(1, $i - 1),
            ];

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

    public function load(Request $request): bool
    {
        return true;
    }

    public function render(string $context = ''): string
    {
        return '<svg id="' . $this->id() . '"></svg>'
            . '<script>'
            . 'let tasks_' . $this->id() . ' = ' . json_encode($this->data) . ';'
            . 'let gantt_' . $this->id() . ' = new Gantt("#' . $this->id() . '", tasks_' . $this->id() . ');'
            . '</script>';
    }
}