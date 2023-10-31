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

        AssetManager::add('js', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js');
    }

    public function render(): string
    {
        return '<div class="p-3" id="calendar"></div>'
            . '<script>'
            . 'document.addEventListener("DOMContentLoaded", function() {'
            . 'var calendarEl = document.getElementById("calendar");'
            . 'var calendar = new FullCalendar.Calendar(calendarEl, {'
            . 'initialView: "dayGridMonth",'
            . 'events: ' . json_encode($this->cursor) . ','
            . '});'
            . 'calendar.render();'
            . '});'
            . '</script>';
    }
}