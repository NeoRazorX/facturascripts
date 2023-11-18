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
use FacturaScripts\Core\UI\Widget\WidgetDatetime;
use FacturaScripts\Core\UI\Widget\WidgetFilemanager;
use FacturaScripts\Core\UI\Widget\WidgetMoney;
use FacturaScripts\Core\UI\Widget\WidgetNumber;
use FacturaScripts\Core\UI\Widget\WidgetSelect;
use FacturaScripts\Core\UI\Widget\WidgetText;
use FacturaScripts\Core\UI\Widget\WidgetTextarea;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Familia;
use FacturaScripts\Dinamic\Model\GrupoClientes;
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
        $this->addSection(Section::make('bottom'))
            ->setTitle('Bottom section');

        // añadimos un botón a la sección bottom
        $this->section('bottom')->addButton(Button::make('button4'))
            ->setIcon('fas fa-plus-square')
            ->setColor('success')
            ->setLabel('Botón 4')
            ->setDescription('Descripción del botón 4');

        // añadimos un tab a la sección bottom
        $this->section('bottom')->addTab(TabCards::make('tab7'))
            ->setLabel('Galería');

        // añadimos una pestaña de gráficos
        $this->section('bottom')->addTab(TabCharts::make('tab8'))
            ->setLabel('Gráficos');

        // añadimos una pestaña con un kanban
        $this->section('bottom')->addTab(TabKanban::make('tab10'))
            ->setLabel('Kanban');

        // añadimos una pestaña con un diagrama de gantt
        $this->section('bottom')->addTab(TabGantt::make('tab11'))
            ->setLabel('Gantt');

        // añadimos una pestaña con un un gestor de archivos
        $this->section('bottom')->addTab(TabFiles::make('tab12'))
            ->setLabel('Archivos');
    }

    protected function addSectionMain(): void
    {
        $this->addSection(Section::make('main'))
            ->setTitle('Main section')
            ->setPosition(1);

        // añadimos un botón a la sección main
        $this->section('main')->addButton(Button::make('button4'))
            ->setLabel('Botón 4');

        // añadimos un dropdown a la sección main, con 2 enlaces, un separador y un tercer enlace
        $this->section('main')->addButton(Dropdown::make('dropdown1'))
            ->setIcon('fas fa-list')
            ->setColor('info')
            ->setDescription('Descripción del dropdown')
            ->addLink('https://www.google.com', 'link 1', [], 'fas fa-plus-square')
            ->addLink('https://www.youtube.com', 'link 2', [], 'fab fa-youtube')
            ->addLinkSeparator()
            ->addLink('https://www.twitter.com', 'link 3', [], 'fab fa-twitter');

        // añadimos un modal
        $this->section('main')->addModal(Modal::make('modal2'))
            ->setTitle('Modal 2');

        // enlazamos el modal con el dropdown
        $this->section('main')->button('dropdown1')->addLinkModal(
            $this->section('main')->modal('modal2'),
            'Modal 2',
            [],
            'fas fa-window-maximize'
        );

        // añadimos 2 pestañas de listado a la sección main
        $this->section('main')->addTab(TabTable::make('tab1'))
            ->setLabel('Listado 1')
            ->setModel(new Producto())
            ->addWidget(WidgetText::make('reference', 'referencia'))
            ->addWidget(WidgetTextarea::make('description', 'descripcion'))
            ->addWidget(
                WidgetSelect::make('family', 'codfamilia')
                    ->setOptionsFromModel(new Familia())
            )
            ->addWidget(WidgetMoney::make('price', 'precio')->setAlign('right'))
            ->addWidget(WidgetNumber::make('stock', 'stockfis')->setAlign('right'))
            ->addWidget(WidgetCheckbox::make('locked', 'bloqueado')->setAlign('center'))
            ->addWidget(WidgetDatetime::make('update', 'actualizado')->setAlign('right'));

        $this->section('main')->addTab(TabTable::make('tab2'))
            ->setLabel('Listado 2')
            ->setModel(new Cliente())
            ->addWidget(WidgetText::make('name', 'nombre'))
            ->addWidget(WidgetText::make('cifnif', 'cifnif', 'fiscal-number'))
            ->addWidget(WidgetText::make('phone', 'telefono'))
            ->addWidget(WidgetText::make('email'));

        // añadimos un tab de listado de formularios, y lo ponemos en posición 1
        $this->section('main')->addTab(TabFormList::make('tab4'))
            ->setLabel('+Formularios')
            ->setPosition(1);

        // añadimos un tab con un calendario
        $this->section('main')->addTab(TabCalendar::make('tab5'))
            ->setLabel('Calendario');

        // añadimos un tab con un mapa
        $this->section('main')->addTab(TabMap::make('tab6'))
            ->setLabel('Mapa');

        // añadimos una pestaña datatable
        $this->section('main')->addTab(TabDataTable::make('tab9'))
            ->setLabel('Datatable');
    }

    protected function addSectionTop(): void
    {
        $this->addSection(Section::make('top'))
            ->setTitle('Top section')
            ->setDescription('Descripción de la sección top')
            ->setIcon('fas fa-dashboard')
            ->addNavLinks('#', 'Enlace 1')
            ->addNavLinks('#', 'Enlace 2')
            ->addNavLinks('#', 'Enlace 3');

        // añadimos 2 botones
        $this->section('top')->addButton(Button::make('button1'))
            ->setIcon('fas fa-plus-square')
            ->setColor('success')
            ->setLabel('Botón 1')
            ->setDescription('Descripción del botón 1')
            ->onClick('function1');

        $this->section('top')->addButton(Button::make('button2'))
            ->setIcon('fas fa-check-square')
            ->setCounter(5);

        // añadimos un tercer botón y lo ponemos después del botón 1
        $this->section('top')->addButton(Button::make('button3'))
            ->setPosition(1)
            ->setLabel('Botón 3');

        // añadimos un modal
        $this->section('top')->addModal(Modal::make('modal1'))
            ->setTitle('Modal 1');

        // enlazamos el botón 3 con el modal 1
        $this->section('top')->button('button3')->linkModal(
            $this->section('top')->modal('modal1')
        );

        // añadimos 2 cajas de información a la sección top
        $this->section('top')->addInfoBox(InfoBox::make('info1'))
            ->setColor('success')
            ->setIcon('fas fa-chart-line')
            ->setTitle('Información 1')
            ->setDescription('Descripción de la información 1')
            ->setCounter(5);

        $this->section('top')->addInfoBox(InfoBox::make('info2'))
            ->setColor('danger')
            ->setIcon('fas fa-chart-pie')
            ->setTitle('Información 2')
            ->setDescription('Descripción de la información 2');

        // añadimos un tab de formulario a la sección top
        $this->section('top')->addTab(TabForm::make('tab1'))
            ->setModel(new Cliente())
            ->addWidget(WidgetText::make('name', 'nombre')->setCols(3))
            ->addWidget(WidgetText::make('cifnif', 'cifnif', 'fiscal-number')->setCols(3))
            ->addWidget(WidgetText::make('phone', 'telefono')->setCols(3))
            ->addWidget(
                WidgetSelect::make('group', 'codgrupo')
                    ->setCols(3)
                    ->setOptionsFromModel(new GrupoClientes())
                    ->createOptionForm([
                        WidgetText::make('name', 'nombre')
                    ])
            )
            ->addWidget(
                WidgetTextarea::make('observations', 'observaciones')
                ->setCols(12)
            )
            ->addWidget(WidgetFilemanager::make('logo', 'idfile'))
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
