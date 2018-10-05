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
 * Controller to edit a single item from the AlbaranCliente model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 * @author Rafael San José Tovar <rafael.sanjose@x-netdigital.com>
 */
class EditFacturaProveedor extends ExtendedController\PurchaseDocumentController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'invoice';
        $pagedata['menu'] = 'purchases';
        $pagedata['icon'] = 'fas fa-copy';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->addListView('EditAsiento', 'asiento', 'accounting-entries', 'fas fa-balance-scale');
    }

    /**
     * Return the document class name.
     *
     * @return string
     */
    protected function getModelClassName()
    {
        return 'FacturaProveedor';
    }

    /**
     * Load data view procedure
     *
     * @param string                      $viewName
     * @param ExtendedController\EditView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'EditAsiento':
                $where = [
                    new DataBaseWhere('idasiento', $this->getViewModelValue('Document', 'idasiento')),
                    new DataBaseWhere('idasiento', $this->getViewModelValue('Document', 'idasientop'), '=', 'OR')
                ];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
        }
    }
}
