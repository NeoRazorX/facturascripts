<?php

namespace FacturaScripts\Core\UI;

use FacturaScripts\Core\Lib\AssetManager;
use FacturaScripts\Core\Template\SectionTab;

class TabDataTable extends SectionTab
{
    public $cursor = [];

    public function __construct()
    {
        $this->icon = 'fas fa-table';

        AssetManager::add('css', 'https://unpkg.com/tabulator-tables/dist/css/tabulator.min.css');
        AssetManager::add('js', 'https://unpkg.com/tabulator-tables/dist/js/tabulator.min.js');

        // añadimos datos de prueba
        foreach (range(1, 200) as $i) {
            $this->cursor[] = [
                'id' => $i,
                'name' => 'name ' . $i,
                'surname' => 'surname ' . $i,
                'gender' => rand(0, 1) ? 'male' : 'female',
                'height' => rand(150, 200),
                'date' => date('Y-m-d', strtotime('-' . rand(1000, 99999) . ' days')),
            ];

            $this->counter++;
        }
    }

    public function render(): string
    {
        return '<div id="example-table"></div>'
            . '<script>'
            . 'var table = new Tabulator("#example-table", {'
            . 'data:' . json_encode($this->cursor) . ','
            . 'columns:['
            . '{title:"Id", field:"id"},'
            . '{title:"Name", field:"name", editor:"input"},'
            . '{surname:"Surname", field:"surname", editor:"input"},'
            . '{gender:"Gender", field:"gender", editor:"select", editorParams:{values:["male", "female"]}},'
            . '{height:"Height", field:"height", editor:"number"},'
            . '{date:"Date Of Birth", field:"date", editor:"input"},'
            . '],'
            . 'layout:"fitColumns",'
            . '});'
            . '</script>';
    }
}