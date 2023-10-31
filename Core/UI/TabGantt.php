<?php

namespace FacturaScripts\Core\UI;

use FacturaScripts\Core\Template\SectionTab;
use FacturaScripts\Dinamic\Lib\AssetManager;

class TabGantt extends SectionTab
{
    public $cursor = [];

    public function __construct()
    {
        $this->icon = 'fa-solid fa-chart-gantt';

        AssetManager::add('css', 'https://cdnjs.cloudflare.com/ajax/libs/frappe-gantt/0.6.1/frappe-gantt.min.css');
        AssetManager::add('js', 'https://cdnjs.cloudflare.com/ajax/libs/frappe-gantt/0.6.1/frappe-gantt.min.js');

        // creamos algunos datos de ejemplo
        foreach (range(1, rand(3, 10)) as $i) {
            $start = date('Y-m-d', strtotime('+' . $i . ' days'));
            $end = date('Y-m-d', strtotime('+' . ($i + rand(1, 10)) . ' days'));

            $this->cursor[] = [
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

    public function render(): string
    {
        return '<svg id="gantt"></svg>'
            . '<script>'
            . 'let tasks = ' . json_encode($this->cursor) . ';'
            . 'let gantt = new Gantt("#gantt", tasks);'
            . '</script>';
    }
}