<?php

namespace FacturaScripts\Core\UI;

use FacturaScripts\Core\Lib\AssetManager;
use FacturaScripts\Core\Template\SectionTab;

class TabKanban extends SectionTab
{
    public function __construct()
    {
        $this->icon = 'fas fa-tasks';

        AssetManager::add('css', 'https://www.riccardotartaglia.it/jkanban/dist/jkanban.min.css');
        AssetManager::add('js', 'https://www.riccardotartaglia.it/jkanban/dist/jkanban.min.js');
    }

    public function render(): string
    {
        return '<div id="myKanban"></div>'
            . '<script>'
            . "var kanban1 = new jKanban({
        element:'#myKanban',
        boards  :[
            {
                'id' : '_todo',
                'title'  : 'Try Drag me!',
                'item'  : [
                    {
                        'title':'You can drag me too',
                    },
                    {
                        'title':'Buy Milk',
                    }
                ]
            },
            {
                'id' : '_working',
                'title'  : 'Working',
                'item'  : [
                    {
                        'title':'Do Something!',
                    },
                    {
                        'title':'Run?',
                    }
                ]
            },
            {
                'id' : '_done',
                'title'  : 'Done',
                'item'  : [
                    {
                        'title':'All right',
                    },
                    {
                        'title':'Ok!',
                    }
                ]
            }
        ]
    });"
            . '</script>';
    }
}
