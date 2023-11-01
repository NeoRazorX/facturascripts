<?php

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Template\Controller\UIController;
use FacturaScripts\Core\UI\Dropdown;
use FacturaScripts\Core\UI\Section;
use FacturaScripts\Core\UI\Tab\TabCalendar;
use FacturaScripts\Core\UI\Tab\TabCards;
use FacturaScripts\Core\UI\Tab\TabCharts;
use FacturaScripts\Core\UI\Tab\TabDataTable;
use FacturaScripts\Core\UI\Tab\TabForm;
use FacturaScripts\Core\UI\Tab\TabFormList;
use FacturaScripts\Core\UI\Tab\TabGantt;
use FacturaScripts\Core\UI\Tab\TabKanban;
use FacturaScripts\Core\UI\Tab\TabList;
use FacturaScripts\Core\UI\Tab\TabMap;

class DashboardUI extends UIController
{
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        // añadimos un par de secciones
        $this->addSection('top', new Section('top'))
            ->setTitle('Top section');

        $this->addSection('bottom')
            ->setTitle('Bottom section');

        $this->addSection('main')
            ->setTitle('Main section')
            ->setPosition(1);

        // añadimos 2 botones a la sección top
        $this->section('top')->addButton('button1')
            ->setIcon('fas fa-plus-square')
            ->setColor('success')
            ->setDescription('Descripción del botón 1');
        $this->section('top')->addButton('button2')
            ->setCounter(5);

        // añadimos un tercer botón y lo ponemos después del botón 1
        $this->section('top')->addButton('button3')
            ->setPosition(1);

        // añadimos un botón a la sección main
        $this->section('main')->addButton('button4');

        // añadimos un dropdown a la sección main, con 2 enlaces, un separador y un tercer enlace
        $this->section('main')->addButton('dropdown1', new Dropdown('dropdown1'))
            ->setIcon('fas fa-list')
            ->setColor('info')
            ->setDescription('Descripción del dropdown')
            ->addLink('link1', 'https://www.google.com', 'fas fa-plus-square')
            ->addLink('link2', 'https://www.google.com')
            ->addLink('-', '#')
            ->addLink('link3', 'https://www.google.com');

        // añadimos un botón a la sección bottom
        $this->section('bottom')->addButton('button5');

        // añadimos un tab de formulario a la sección top
        $this->section('top')->addTab('tab1', new TabForm('tab1'));

        // añadimos 2 pestañas de listado a la sección main
        $this->section('main')->addTab('tab1', new TabList('tab1'))
            ->setLabel('Listado 1');
        $this->section('main')->addTab('tab2', new TabList('tab2'))
            ->setLabel('Listado 2');

        // añadimos un tercer tab y lo ponemos después del tab 1
        $this->section('main')->addTab('tab3', new TabForm('tab3'))
            ->setLabel('Formulario')
            ->setPosition(1);

        // añadimos un tab de listado de formularios
        $this->section('main')->addTab('tab4', new TabFormList('tab4'))
            ->setLabel('+Formularios');

        // añadimos un tab con un calendario
        $this->section('main')->addTab('tab5', new TabCalendar('tab5'))
            ->setLabel('Calendario');

        // añadimos un tab con un mapa
        $this->section('main')->addTab('tab6', new TabMap('tab6'))
            ->setLabel('Mapa');

        // añadimos un tab a la sección bottom
        $this->section('bottom')->addTab('tab7', new TabCards('tab7'))
            ->setLabel('Galería');

        // añadimos una pestaña de gráficos
        $this->section('bottom')->addTab('tab8', new TabCharts('tab8'))
            ->setLabel('Gráficos');

        // añadimos una pestaña datatable
        $this->section('main')->addTab('tab9', new TabDataTable('tab9'))
            ->setLabel('Datatable');

        // añadimos una pestaña con un kanban
        $this->section('bottom')->addTab('tab10', new TabKanban('tab10'))
            ->setLabel('Kanban');

        // añadimos una pestaña con un diagrama de gantt
        $this->section('bottom')->addTab('tab11', new TabGantt('tab11'))
            ->setLabel('Gantt');
    }
}