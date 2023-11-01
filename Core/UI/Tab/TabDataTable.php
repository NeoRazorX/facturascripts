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

use FacturaScripts\Core\Lib\AssetManager;
use FacturaScripts\Core\Template\UI\SectionTab;

class TabDataTable extends SectionTab
{
    /** @var array */
    public $data = [];

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->icon = 'fas fa-table';

        AssetManager::add('css', 'https://unpkg.com/tabulator-tables/dist/css/tabulator.min.css');
        AssetManager::add('js', 'https://unpkg.com/tabulator-tables/dist/js/tabulator.min.js');

        // añadimos datos de prueba
        foreach (range(1, 200) as $i) {
            $this->data[] = [
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

    public function jsInitFunction(): string
    {
        return '';
    }

    public function jsRedrawFunction(): string
    {
        return '';
    }

    public function render(string $context = ''): string
    {
        return '<div id="' . $this->id() . '"></div>'
            . '<script>'
            . 'let table_' . $this->id() . ' = new Tabulator("#' . $this->id() . '", {'
            . 'data:' . json_encode($this->data) . ','
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