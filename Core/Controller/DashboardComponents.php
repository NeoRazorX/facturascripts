<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Component\ActionResult;
use FacturaScripts\Core\Component\ComponentNumber;
use FacturaScripts\Core\Component\ComponentSelect;
use FacturaScripts\Core\Component\ComponentText;
use FacturaScripts\Core\Component\ComponentTextarea;
use FacturaScripts\Core\Component\UIController;
use FacturaScripts\Core\Tools;

/**
 * Controlador de demostración que ejercita todos los tipos de componentes disponibles.
 *
 * Sirve como ejemplo vivo y prueba de integración del sistema de componentes. Muestra
 * ComponentText (con icono y validación de email), ComponentSelect (con claves
 * traducidas), ComponentNumber y ComponentTextarea en un único formulario.
 * Accesible en /DashboardComponents.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class DashboardComponents extends UIController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'reports';
        $data['title'] = 'dashboard-components';
        $data['icon'] = 'fa-solid fa-puzzle-piece';
        return $data;
    }

    protected function createUI(): void
    {
        // -- Contact block ------------------------------------------------
        $this->addComponent(
            ComponentText::make('nombre')
                ->setLabel('name')
                ->setRequired()
                ->setCols(4)
        );

        $this->addComponent(
            ComponentText::make('email')
                ->setLabel('email')
                ->setIcon('fa-solid fa-envelope')
                ->addRule('email')
                ->setCols(4)
        );

        $this->addComponent(
            ComponentText::make('telefono')
                ->setLabel('phone')
                ->setIcon('fa-solid fa-phone')
                ->setCols(4)
        );

        // -- Extra data ---------------------------------------------------
        $this->addComponent(
            ComponentSelect::make('tipo')
                ->setLabel('type')
                ->setCols(3)
                ->setValuesFromArrayKeys([
                    'cliente'   => 'Cliente',
                    'proveedor' => 'Proveedor',
                    'otro'      => 'Otro',
                ], true)
        );

        $this->addComponent(
            ComponentNumber::make('importe')
                ->setLabel('amount')
                ->setMin(0)
                ->setDecimals(2)
                ->setCols(3)
        );

        $this->addComponent(
            ComponentTextarea::make('observaciones')
                ->setLabel('observations')
                ->setRows(4)
                ->setCols(12)
        );

        // -- Register save handler ----------------------------------------
        $this->onEvent('save', fn() => $this->save());
    }

    protected function save(): ActionResult
    {
        Tools::log()->notice('record-updated-correctly');

        return ActionResult::make();
    }
}
