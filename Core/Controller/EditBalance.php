<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * Controller to edit a single item from the Balance model
 *
 * @author PC REDNET S.L. <luismi@pcrednet.com>
 */
class EditBalance extends ExtendedController\PanelController
{

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addEditView('Balance', 'EditBalance', 'balance');
        $this->addEditListView('BalanceCuenta', 'EditBalanceCuenta', 'balance-account');
        $this->addEditListView('BalanceCuentaA', 'EditBalanceCuentaA', 'balance-account-abreviated');
    }

    /**
     * Load view data procedure
     *
     * @param string                      $keyView
     * @param ExtendedController\EditView $view
     */
    protected function loadData($keyView, $view)
    {
        switch ($keyView) {
            case 'EditBalance':
                $code = $this->request->get('code');
                $view->loadData($code);
                break;

            case 'EditBalanceCuenta':
            case 'EditBalanceCuentaA':
                $codbalance = $this->getViewModelValue('EditBalance', 'codbalance');
                $where = [new DataBaseWhere('codbalance', $codbalance)];
                $view->loadData(false, $where, [], 0, 0);
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
        $pagedata['title'] = 'balance';
        $pagedata['menu'] = 'accounting';
        $pagedata['icon'] = 'fa-clipboard';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
