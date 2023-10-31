<?php

namespace FacturaScripts\Core\UI;

use FacturaScripts\Core\Template\SectionTab;
use FacturaScripts\Dinamic\Lib\AssetManager;

class TabCalendar extends SectionTab
{
    public $cursor = [];

    public function __construct()
    {
        $this->icon = 'fas fa-calendar-alt';

        AssetManager::add('js', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js');

        // añadimos datos de prueba
        foreach (range(1, rand(9, 50)) as $num) {
            $date = date('Y-m-' . rand(1, 28));

            $this->cursor[] = [
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
        return 'let cal_' . $this->name . ' = null;';
    }

    public function jsRedrawFunction(): string
    {
        return 'cal_' . $this->name . '.render();';
    }

    public function render(): string
    {
        $name = 'cal_' . $this->name;

        return '<div class="p-3" id="' . $name . '"></div>' . "\n"
            . '<script>' . "\n"
            . '   document.addEventListener("DOMContentLoaded", function() {' . "\n"
            . '      let ' . $name . '_el = document.getElementById("' . $name . '");' . "\n"
            . '      ' . $name . ' = new FullCalendar.Calendar(' . $name . '_el, {' . "\n"
            . '         initialView: "dayGridMonth",' . "\n"
            . '         events: ' . json_encode($this->cursor) . ',' . "\n"
            . '      });' . "\n"
            . '      ' . $name . '.render();' . "\n"
            . '   });' . "\n"
            . '</script>';
    }
}