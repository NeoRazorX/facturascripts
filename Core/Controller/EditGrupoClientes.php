<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Lib\ExtendedController;

/**
 * Controller to edit a single item from the GrupoClientes model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Artex Trading sa             <jcuello@artextrading.com>
 * @author Nazca Networks               <comercial@nazcanetworks.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class EditGrupoClientes extends ExtendedController\EditController
{

    /**
     * Returns the class name of the model to use in the editView.
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'GrupoClientes';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'customer-group';
        $pagedata['menu'] = 'sales';
        $pagedata['icon'] = 'fas fa-folder-open';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->addListView('ListCliente', 'Cliente', 'customers', 'fas fa-users');

        /// settings
        $this->views['ListCliente']->disableColumn('group', true);
        $this->views['ListCliente']->settings['btnNew'] = false;
        $this->views['ListCliente']->settings['btnDelete'] = false;
    }

    /**
     * Procedure responsible for loading the data to be displayed.
     *
     * @param string                      $viewName
     * @param ExtendedController\EditView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListCliente':
                $codgrupo = $this->getViewModelValue('EditGrupoClientes', 'codgrupo');
                $where = [new DataBaseWhere('codgrupo', $codgrupo)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
