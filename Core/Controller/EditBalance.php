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
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Controller to edit a single item from the Balance model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author PC REDNET S.L.       <luismi@pcrednet.com>
 */
class EditBalance extends EditController
{

    /**
     * Returns the model name.
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'Balance';
    }

    /**
     * Returns basic page attributes.
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'accounting-balance';
        $data['icon'] = 'fas fa-clipboard';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->addEditListView('EditBalanceCuenta', 'BalanceCuenta', 'balance-account', 'fas fa-book');
        $this->addEditListView('EditBalanceCuentaA', 'BalanceCuentaA', 'balance-account-abreviated', 'fas fa-clipboard-list');
    }

    /**
     * Load view data procedure
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'EditBalanceCuenta':
            case 'EditBalanceCuentaA':
                $codbalance = $this->getViewModelValue($this->getMainViewName(), 'codbalance');
                $where = [new DataBaseWhere('codbalance', $codbalance)];
                $view->loadData('', $where, ['id' => 'DESC']);
                break;

            default:
                parent::loadData($viewName, $view);
        }
    }
}
