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

class TabCalendar extends SectionTab
{
    /** @var array */
    public $data = [];

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->icon = 'fas fa-calendar-alt';

        AssetManager::add('js', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js');

        // añadimos datos de prueba
        foreach (range(1, rand(9, 50)) as $num) {
            $date = date('Y-m-' . rand(1, 28));

            $this->data[] = [
                'id' => $num,
                'title' => 'Evento ' . $num,
                'start' => $date,
                'end' => date('Y-m-d', strtotime($date . ' +' . rand(1, 3) . ' days')),
                'color' => array_slice(['red', 'blue', 'green', 'yellow', 'orange', 'purple', 'pink'], rand(0, 6), 1)[0],
            ];

            $this->counter++;
        }
    }

    public function jsInitFunction(): string
    {
        return 'let cal_' . $this->id() . ' = null;';
    }

    public function jsRedrawFunction(): string
    {
        return 'cal_' . $this->id() . '.render();';
    }

    public function render(string $context = ''): string
    {
        $name = 'cal_' . $this->id();

        return '<div class="p-3" id="' . $name . '"></div>' . "\n"
            . '<script>' . "\n"
            . '   document.addEventListener("DOMContentLoaded", function() {' . "\n"
            . '      let ' . $name . '_el = document.getElementById("' . $name . '");' . "\n"
            . '      ' . $name . ' = new FullCalendar.Calendar(' . $name . '_el, {' . "\n"
            . '         initialView: "dayGridMonth",' . "\n"
            . '         events: ' . json_encode($this->data) . ',' . "\n"
            . '      });' . "\n"
            . '      ' . $name . '.render();' . "\n"
            . '   });' . "\n"
            . '</script>';
    }
}