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

class TabKanban extends SectionTab
{
    /** @var array */
    public $boards = [];

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->icon = 'fab fa-trello';

        AssetManager::add('css', 'https://www.riccardotartaglia.it/jkanban/dist/jkanban.min.css');
        AssetManager::add('js', 'https://www.riccardotartaglia.it/jkanban/dist/jkanban.min.js');

        // añadimos algunos datos de prueba
        $taskNum = 1;
        foreach (range(1, rand(2, 4)) as $board) {
            $tasks = [];
            foreach (range(1, rand(1, 5)) as $task) {
                $tasks[] = ['title' => 'Task ' . $taskNum];
                $taskNum++;
                $this->counter++;
            }

            $this->boards[] = [
                'id' => '_board_' . $board,
                'title' => 'Board ' . $board,
                'item' => $tasks,
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

    public function render(string $context = ''): string
    {
        return '<div class="mt-3 mb-4" id="' . $this->id() . '"></div>' . "\n"
            . '<script>' . "\n"
            . "   let kanban_" . $this->id() . " = new jKanban({\n"
            . "      element:'#" . $this->id() . "',\n"
            . "      boards:" . json_encode($this->boards) . ",\n"
            . "});\n"
            . "</script>\n";
    }
}
