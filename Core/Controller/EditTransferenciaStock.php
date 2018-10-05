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

use FacturaScripts\Core\Lib\ExtendedController;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Controller to edit a transfer of stock
 *
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 */
class EditTransferenciaStock extends ExtendedController\PanelController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'stock-transfer';
        $pagedata['menu'] = 'warehouse';
        $pagedata['icon'] = 'fas fa-exchange-alt';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addEditView('EditTransferenciaStock', 'TransferenciaStock', 'transfer-head');
        $this->addEditListView('EditLineaTransferenciaStock', 'LineaTransferenciaStock', 'lines-transfer');

        /// tabs on bottom
        $this->setTabsPosition('bottom');
    }

    protected function insertAction()
    {
        parent::insertAction();
        $this->views[$this->active]->model->usuario = $this->user->nick;
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
            case 'EditTransferenciaStock':
                $code = $this->request->get('code');
                $view->loadData($code);
                break;

            case 'EditLineaTransferenciaStock':
                $idtransferencia = $this->getViewModelValue('EditTransferenciaStock', 'idtrans');
                $where = [new DataBaseWhere('idtrans', $idtransferencia)];
                $view->loadData('', $where, [], 0, 0);
                break;
        }
    }
}
