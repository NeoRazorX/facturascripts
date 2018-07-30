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
 * Controller to edit a single item from the Agente model
 *
 * @author Raul
 */
class EditAgente extends ExtendedController\PanelController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'agent';
        $pagedata['menu'] = 'admin';
        $pagedata['icon'] = 'fa-id-badge';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Load Views
     */
    protected function createViews()
    {
        $this->addEditView('EditAgente', 'Agente', 'agent');
        $this->addListView('ListFacturaCliente', 'FacturaCliente', 'invoices', 'fa-copy');
        $this->addListView('ListAlbaranCliente', 'AlbaranCliente', 'delivery-notes', 'fa-copy');
        $this->addListView('ListPedidoCliente', 'PedidoCliente', 'orders', 'fa-copy');
        $this->addListView('ListPresupuestoCliente', 'PresupuestoCliente', 'estimations', 'fa-copy');
    }

    /**
     * Load view data procedure
     *
     * @param string                      $viewName
     * @param ExtendedController\EditView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'EditAgente':
                $code = $this->request->get('code');
                $view->loadData($code);
                break;

            case 'ListAlbaraneCliente':
            case 'ListFacturaCliente':
            case 'ListPedidoCliente':
            case 'ListPresupuestoCliente':
                $codagente = $this->getViewModelValue('EditAgente', 'codagente');
                $where = [new DataBaseWhere('codagente', $codagente)];
                $view->loadData('', $where);
                break;
        }
    }
}
