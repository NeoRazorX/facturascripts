<?php

namespace FacturaScripts\Core\UI;

use FacturaScripts\Core\Template\SectionTab;

class TabList extends SectionTab
{
    public function render(): string
    {
        $columns = range(1, rand(3, 6));
        $rows = [];
        for ($i = 0; $i < rand(9, 49); $i++) {
            $row = [];
            for ($j = 0; $j < count($columns); $j++) {
                $row[] = 'Valor ' . rand(1, 100);
            }
            $rows[] = $row;
        }

        $html = '<div class="table-responsive">'
            . '<table class="table table-striped table-hover table-sm">'
            . '<thead>'
            . '<tr>';

        foreach ($columns as $column) {
            $html .= '<th>Columna ' . $column . '</th>';
        }

        $html .= '</tr>'
            . '</thead>'
            . '<tbody>';

        foreach ($rows as $row) {
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