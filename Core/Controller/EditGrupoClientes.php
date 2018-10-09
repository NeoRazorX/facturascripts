<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to edit a single item from the GrupoClientes model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author Nazca Networks <comercial@nazcanetworks.com>
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 */
class EditGrupoClientes extends ExtendedController\EditController
{

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
        $this->addEditView('EditGrupoClientes', 'GrupoClientes', 'customer-group');
        $this->addListView('ListCliente', 'Cliente', 'customers', 'fas fa-users');
        $this->setTabsPosition('bottom');

        /// Disable columns
        $this->views['ListCliente']->disableColumn('group', true);
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
            case 'EditGrupoClientes':
                $code = $this->request->get('code');
                $view->loadData($code);
                break;

            case 'ListCliente':
                $codgrupo = $this->getViewModelValue('EditGrupoClientes', 'codgrupo');
                $where = [new DataBaseWhere('codgrupo', $codgrupo)];
                $view->loadData('', $where);
                break;
        }
    }

    /**
     * Returns the class name of the model to use in the editView.
     * 
     * @return String
     */
    public function getModelClassName() : string
    {
        return 'Cuenta';
    }
}
