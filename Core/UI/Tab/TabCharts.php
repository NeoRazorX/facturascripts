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

class TabCharts extends SectionTab
{
    /** @var array */
    public $data = [];

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->icon = 'fas fa-chart-line';

        // reemplazar por https://frappe.io/charts
        AssetManager::add('js', 'node_modules/chart.js/dist/Chart.min.js');

        // añadimos datos de prueba
        foreach (range(1, 50) as $i) {
            $this->data[] = [
                'date' => date('Y-m-d', strtotime('2022-11-06 + ' . $i . ' days')),
                'total' => rand(0, 999),
            ];
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
        $chartId = 'chart_' . $this->id();

        $labels = [];
        $data = [];
        foreach ($this->data as $row) {
            $labels[] = $row['date'];
            $data[] = $row['total'];
        }

        return '<div style="width: 100%; height: 400px; margin-bottom: 25px;">'
            . '<canvas id="' . $chartId . '"></canvas>'
            . '<script>'
            . "let ctx" . $this->id() . " = document.getElementById('" . $chartId . "').getContext('2d');let myChart"
            . $this->id() . " = new Chart(ctx" . $this->id() . ", {"
            . "type: 'line',"
            . "data: {"
            . "labels: ['" . implode("','", $labels) . "'],"
            . "datasets: [{"
            . "label: 'totals',"
            . "data: [" . implode(',', $data) . "],"
            . "backgroundColor: ['rgba(240, 55, 55, 0.2)'],"
            . "borderColor: ['rgba(240, 55, 55, 1)'],"
            . "borderWidth: 1"
            . "}]"
            . "},"
            . "options: {"
            . "responsive: true,"
            . "maintainAspectRatio: false"
            . "}"
            . "});"
            . "</script>"
            . '</div>';
    }
}