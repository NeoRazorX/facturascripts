<?php

namespace FacturaScripts\Core\UI;

use FacturaScripts\Core\Template\SectionTab;
use FacturaScripts\Dinamic\Lib\AssetManager;

class TabCharts extends SectionTab
{
    public $data = [];

    public function __construct()
    {
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

    public function render(): string
    {
        $chartId = 'chart_' . $this->name;

        $labels = [];
        $data = [];
        foreach ($this->data as $row) {
            $labels[] = $row['date'];
            $data[] = $row['total'];
        }

        return '<div style="width: 100%; height: 400px; margin-bottom: 25px;">'
            . '<canvas id="' . $chartId . '"></canvas>'
            . '<script>'
            . "let ctx" . $this->name . " = document.getElementById('" . $chartId . "').getContext('2d');let myChart" . $this->name . " = new Chart(ctx" . $this->name . ", {"
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