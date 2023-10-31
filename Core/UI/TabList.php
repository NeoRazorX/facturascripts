<?php

namespace FacturaScripts\Core\UI;

use FacturaScripts\Core\Template\SectionTab;

class TabList extends SectionTab
{
    public $cursor = [];

    public function __construct()
    {
        $this->icon = 'fas fa-list';

        // creamos algunos datos de ejemplo
        $columns = range(1, rand(3, 9));
        for ($i = 0; $i < rand(9, 49); $i++) {
            $row = [];
            for ($j = 0; $j < count($columns); $j++) {
                $row[] = 'Valor ' . rand(1, 100);
            }

            $this->cursor[] = $row;
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

        foreach (array_keys($this->cursor[0]) as $column) {
            $html .= '<th>Columna ' . $column . '</th>';
        }

        $html .= '</tr>'
            . '</thead>'
            . '<tbody>';

        foreach ($this->cursor as $row) {
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