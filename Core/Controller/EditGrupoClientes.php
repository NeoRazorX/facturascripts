<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
 */
class EditGrupoClientes extends ExtendedController\PanelController
{

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addEditView('\FacturaScripts\Dinamic\Model\GrupoClientes', 'EditGrupoClientes', 'customer-group');
        $this->addListView('\FacturaScripts\Dinamic\Model\Cliente', 'ListCliente', 'customers', 'fa-users');
        $this->setTabsPosition('bottom');

        /// Disable columns
        $this->views['ListCliente']->disableColumn('group', true);
    }

    /**
     * Procedure responsible for loading the data to be displayed.
     *
     * @param string                      $keyView
     * @param ExtendedController\EditView $view
     */
    protected function loadData($keyView, $view)
    {
        switch ($keyView) {
            case 'EditGrupoClientes':
                $code = $this->request->get('code');
                $view->loadData($code);
                break;

            case 'ListCliente':
                $codgrupo = $this->getViewModelValue('EditGrupoClientes', 'codgrupo');
                $where = [new DataBaseWhere('codgrupo', $codgrupo)];
                $view->loadData(false, $where);
                break;
        }
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
        $pagedata['icon'] = 'fa-folder-open';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
