<?php

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Template\Controller\UIController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\UI\Button;
use FacturaScripts\Core\UI\Dropdown;
use FacturaScripts\Core\UI\InfoBox;
use FacturaScripts\Core\UI\Modal;
use FacturaScripts\Core\UI\Section;
use FacturaScripts\Core\UI\Tab\TabCalendar;
use FacturaScripts\Core\UI\Tab\TabCards;
use FacturaScripts\Core\UI\Tab\TabCharts;
use FacturaScripts\Core\UI\Tab\TabDataTable;
use FacturaScripts\Core\UI\Tab\TabFiles;
use FacturaScripts\Core\UI\Tab\TabForm;
use FacturaScripts\Core\UI\Tab\TabFormList;
use FacturaScripts\Core\UI\Tab\TabGantt;
use FacturaScripts\Core\UI\Tab\TabKanban;
use FacturaScripts\Core\UI\Tab\TabMap;
use FacturaScripts\Core\UI\Tab\TabTable;
use FacturaScripts\Core\UI\Widget\WidgetCheckbox;
use FacturaScripts\Core\UI\Widget\WidgetDate;
use FacturaScripts\Core\UI\Widget\WidgetDatetime;
use FacturaScripts\Core\UI\Widget\WidgetMoney;
use FacturaScripts\Core\UI\Widget\WidgetNumber;
use FacturaScripts\Core\UI\Widget\WidgetText;
use FacturaScripts\Core\UI\Widget\WidgetTextarea;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Producto;

class DashboardUI extends UIController
{
    protected function addComponents(): void
    {
        // añadimos un par de secciones
        $this->addSectionTop();
        $this->addSectionMain();
        $this->addSectionBottom();
    }

    protected function addSectionBottom(): void
    {
        $this->addSection(new Section('bottom'))
            ->setTitle('Bottom section');

        // añadimos un botón a la sección bottom
        $this->section('bottom')->addButton(new Button('button4'))
            ->setIcon('fas fa-plus-square')
            ->setColor('success')
            ->setLabel('Botón 4')
            ->setDescription('Descripción del botón 4');

        // añadimos un tab a la sección bottom
        $this->section('bottom')->addTab(new TabCards('tab7'))
            ->setLabel('Galería');

        // añadimos una pestaña de gráficos
        $this->section('bottom')->addTab(new TabCharts('tab8'))
            ->setLabel('Gráficos');

        // añadimos una pestaña con un kanban
        $this->section('bottom')->addTab(new TabKanban('tab10'))
            ->setLabel('Kanban');

        // añadimos una pestaña con un diagrama de gantt
        $this->section('bottom')->addTab(new TabGantt('tab11'))
            ->setLabel('Gantt');

        // añadimos una pestaña con un un gestor de archivos
        $this->section('bottom')->addTab(new TabFiles('tab12'))
            ->setLabel('Archivos');
    }

    protected function addSectionMain(): void
    {
        $this->addSection(new Section('main'))
            ->setTitle('Main section')
            ->setPosition(1);

        // añadimos un botón a la sección main
        $this->section('main')->addButton(new Button('button4'))
            ->setLabel('Botón 4');

        // añadimos un dropdown a la sección main, con 2 enlaces, un separador y un tercer enlace
        $this->section('main')->addButton(new Dropdown('dropdown1'))
            ->setIcon('fas fa-list')
            ->setColor('info')
            ->setDescription('Descripción del dropdown')
            ->addLink('https://www.google.com', 'link 1', [], 'fas fa-plus-square')
            ->addLink('https://www.youtube.com', 'link 2', [], 'fab fa-youtube')
            ->addLinkSeparator()
            ->addLink('https://www.twitter.com', 'link 3', [], 'fab fa-twitter');

        // añadimos un modal
        $this->section('main')->addModal(new Modal('modal2'))
            ->setTitle('Modal 2');

        // enlazamos el modal con el dropdown
        $this->section('main')->button('dropdown1')->addLinkModal(
            $this->section('main')->modal('modal2'),
            'Modal 2',
            [],
            'fas fa-window-maximize'
        );

        // añadimos 2 pestañas de listado a la sección main
        $this->section('main')->addTab(new TabTable('tab1'))
            ->setLabel('Listado 1')
            ->setModel(new Producto())
            ->addWidget(WidgetText::make('reference', 'referencia'))
            ->addWidget(WidgetText::make('description', 'descripcion'))
            ->addWidget(WidgetMoney::make('price', 'precio')->setAlign('right'))
            ->addWidget(WidgetNumber::make('stock', 'stockfis')->setAlign('right'))
            ->addWidget(WidgetCheckbox::make('locked', 'bloqueado')->setAlign('center'))
            ->addWidget(WidgetDatetime::make('update', 'actualizado')->setAlign('right'));

        $this->section('main')->addTab(new TabTable('tab2'))
            ->setLabel('Listado 2')
            ->setModel(new Cliente())
            ->addWidget(WidgetText::make('name', 'nombre'))
            ->addWidget(WidgetText::make('cifnif', 'cifnif', 'fiscal-number'))
            ->addWidget(WidgetText::make('phone', 'telefono'))
            ->addWidget(WidgetText::make('email'));

        // añadimos un tab de listado de formularios, y lo ponemos en posición 1
        $this->section('main')->addTab(new TabFormList('tab4'))
            ->setLabel('+Formularios')
            ->setPosition(1);

        // añadimos un tab con un calendario
        $this->section('main')->addTab(new TabCalendar('tab5'))
            ->setLabel('Calendario');

        // añadimos un tab con un mapa
        $this->section('main')->addTab(new TabMap('tab6'))
            ->setLabel('Mapa');

        // añadimos una pestaña datatable
        $this->section('main')->addTab(new TabDataTable('tab9'))
            ->setLabel('Datatable');
    }

    protected function addSectionTop(): void
    {
        $this->addSection(new Section('top'))
            ->setTitle('Top section')
            ->setDescription('Descripción de la sección top')
            ->setIcon('fas fa-dashboard')
            ->addNavLinks('#', 'Enlace 1')
            ->addNavLinks('#', 'Enlace 2')
            ->addNavLinks('#', 'Enlace 3');

        // añadimos 2 botones
        $this->section('top')->addButton(new Button('button1'))
            ->setIcon('fas fa-plus-square')
            ->setColor('success')
            ->setLabel('Botón 1')
            ->setDescription('Descripción del botón 1')
            ->onClick('function1');

        $this->section('top')->addButton(new Button('button2'))
            ->setIcon('fas fa-check-square')
            ->setCounter(5);

        // añadimos un tercer botón y lo ponemos después del botón 1
        $this->section('top')->addButton(new Button('button3'))
            ->setPosition(1)
            ->setLabel('Botón 3');

        // añadimos un modal
        $this->section('top')->addModal(new Modal('modal1'))
            ->setTitle('Modal 1');

        // enlazamos el botón 3 con el modal 1
        $this->section('top')->button('button3')->linkModal(
            $this->section('top')->modal('modal1')
        );

        // añadimos 2 cajas de información a la sección top
        $this->section('top')->addInfoBox(new InfoBox('info1'))
            ->setColor('success')
            ->setIcon('fas fa-chart-line')
            ->setTitle('Información 1')
            ->setDescription('Descripción de la información 1')
            ->setCounter(5);

        $this->section('top')->addInfoBox(new InfoBox('info2'))
            ->setColor('danger')
            ->setIcon('fas fa-chart-pie')
            ->setTitle('Información 2')
            ->setDescription('Descripción de la información 2');

        // añadimos un tab de formulario a la sección top
        $this->section('top')->addTab(new TabForm('tab1'))
            ->setModel(new Cliente())
            ->addWidget(WidgetText::make('name', 'nombre')->setCols(3))
            ->addWidget(WidgetText::make('cifnif', 'cifnif', 'fiscal-number')->setCols(3))
            ->addWidget(WidgetText::make('phone', 'telefono')->setCols(3))
            ->addWidget(WidgetText::make('email')->setCols(3))
            ->addWidget(WidgetTextarea::make('observations', 'observaciones'))
            ->onSave('function2');
    }

    protected function function1(): void
    {
        Tools::log()->info('Se ha pulsado el botón 1');
    }

    protected function function2(): void
    {
        Tools::log()->info('Se ha pulsado el botón guardar del formulario');
    }
}
