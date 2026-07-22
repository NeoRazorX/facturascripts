<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2026 Carlos García Gómez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Where;

/**
 * Controlador para editar un único elemento del modelo EstadoDocumento
 *
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 */
class EditEstadoDocumento extends EditController
{
    public function getModelClassName(): string
    {
        return 'EstadoDocumento';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'status-document';
        $data['icon'] = 'fa-solid fa-tag';
        return $data;
    }

    protected function createOtherStatusView($viewName = 'ListEstadoDocumento')
    {
        $this->addListView($viewName, 'EstadoDocumento', 'document-states');

        // disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     * Create tabs or views.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->createOtherStatusView();
    }

    /**
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListEstadoDocumento':
                $idestado = $this->mainTabModelValue('idestado');
                $tipoDoc = $this->mainTabModelValue('tipodoc');
                $where = [
                    Where::eq('tipodoc', $tipoDoc),
                    Where::notEq('idestado', $idestado),
                ];
                $view->loadData('', $where, ['idestado' => 'ASC']);
                break;

            default:
                parent::loadData($viewName, $view);
        }
    }
}
