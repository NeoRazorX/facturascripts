<?php

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Template\UIController;
use FacturaScripts\Core\UI\Dropdown;
use FacturaScripts\Core\UI\Section;
use FacturaScripts\Core\UI\TabForm;
use FacturaScripts\Core\UI\TabList;

class DashboardUI extends UIController
{
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        // añadimos un par de secciones
        $this->addSection('top', new Section())
            ->setTitle('Top section');

        $this->addSection('bottom')
            ->setTitle('Bottom section');

        $this->addSection('main')
            ->setTitle('Main section')
            ->setPosition(1);

        // añadimos 2 botones a la sección top
        $this->section('top')->addButton('button1');
        $this->section('top')->addButton('button2');

        // añadimos un tercer botón y lo ponemos después del botón 1
        $this->section('top')->addButton('button3')
            ->setPosition(1);

        // añadimos un botón a la sección main
        $this->section('main')->addButton('button4');

        // añadimos un dropdown a la sección main, con 2 enlaces, un separador y un tercer enlace
        $this->section('main')->addButton('dropdown1', new Dropdown())
            ->addLink('link1', 'https://www.google.com')
            ->addLink('link2', 'https://www.google.com')
            ->addLink('-', '#')
            ->addLink('link3', 'https://www.google.com');

        // añadimos un botón a la sección bottom
        $this->section('bottom')->addButton('button5');

        // añadimos un tab de formulario a la sección top
        $this->section('top')->addTab('tab1', new TabForm());

        // añadimos 2 pestañas de listado a la sección main
        $this->section('main')->addTab('tab1', new TabList());
        $this->section('main')->addTab('tab2', new TabList());

        // añadimos un tercer tab y lo ponemos después del tab 1
        $this->section('main')->addTab('tab3', new TabForm())
            ->setPosition(1);

        // añadimos un tab a la sección bottom
        $this->section('bottom')->addTab('tab4', new TabList());
    }
}