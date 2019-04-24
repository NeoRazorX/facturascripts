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
use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to edit a single item from the CuentaBanco model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jferrer@artextrading.com>
 */
class EditCuentaBanco extends ExtendedController\EditController
{

    /**
     * Returns the model name
     */
    public function getModelClassName()
    {
        return 'CuentaBanco';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'bank-account';
        $pagedata['icon'] = 'fas fa-piggy-bank';
        $pagedata['menu'] = 'accounting';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->addListView('ListSubcuenta', 'Subcuenta', 'subaccounts', 'fas fa-book');
        $this->setSettings('ListSubcuenta', 'btnNew', false);
    }

    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListSubcuenta':
                $codsubcuenta = $this->getViewModelValue('Edit' . $this->getModelClassName(), 'codsubcuenta');
                $where = [new DataBaseWhere('codsubcuenta', $codsubcuenta)];
                $view->loadData('', $where, ['codejercicio' => 'DESC']);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
