<?php

namespace FacturaScripts\Core\UI;

use FacturaScripts\Core\Template\SectionTab;
use FacturaScripts\Dinamic\Lib\AssetManager;

class TabKanban extends SectionTab
{
    public $boards = [];

    public function __construct()
    {
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

    public function render(): string
    {
        return '<div class="mt-3 mb-4" id="' . $this->name . '"></div>' . "\n"
            . '<script>' . "\n"
            . "   let kanban_" . $this->name . " = new jKanban({\n"
            . "      element:'#" . $this->name . "',\n"
            . "      boards:" . json_encode($this->boards) . ",\n"
            . "});\n"
            . "</script>\n";
    }
}
