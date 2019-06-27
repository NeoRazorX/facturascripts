<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2019 Carlos García Gómez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Controller to edit a single item from the EstadoDocumento model
 *
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 */
class EditEstadoDocumento extends EditController
{

    /**
     * Returns the model name.
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'EstadoDocumento';
    }

    /**
     * Returns basic page attributes.
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'status-document';
        $data['icon'] = 'fas fa-tag';
        return $data;
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createOtherStatusView($viewName = 'ListEstadoDocumento')
    {
        $this->addListView($viewName, 'EstadoDocumento', 'document-states');

        /// disable buttons
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
     * 
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListEstadoDocumento':
                $idestado = $this->getViewModelValue($this->getMainViewName(), 'idestado');
                $tipoDoc = $this->getViewModelValue($this->getMainViewName(), 'tipodoc');
                $where = [
                    new DataBaseWhere('tipodoc', $tipoDoc),
                    new DataBaseWhere('idestado', $idestado, '!='),
                ];
                $view->loadData('', $where, ['idestado' => 'ASC']);
                break;

            default:
                parent::loadData($viewName, $view);
        }
    }
}
