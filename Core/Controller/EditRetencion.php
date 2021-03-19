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
use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Controller to edit a single item from the Retencion model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class EditRetencion extends EditController
{

    /**
     * Const for additionals views
     */
    private const VIEW_CUSTOMER = 'ListCliente';
    private const VIEW_SUPPLIER = 'ListProveedor';

    /**
     * Create the view to display.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->createViewAuxiliar(self::VIEW_CUSTOMER, 'Cliente', 'customers');
        $this->createViewAuxiliar(self::VIEW_SUPPLIER, 'Proveedor', 'suppliers');
        $this->setTabsPosition('bottom');
    }

    /**
     * Returns the model name.
     *
     * @return string
     */
    public function getModelClassName()
    {
        return 'Retencion';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'retention';
        $data['icon'] = 'fas fa-plus-square';
        return $data;
    }

    /**
     * Loads the data to display.
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case self::VIEW_SUPPLIER:
            case self::VIEW_CUSTOMER:
                $mvn = $this->getMainViewName();
                $code = $this->getViewModelValue($mvn, 'codretencion');
                $where = [ new DataBaseWhere('codretencion', $code) ];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    private function createViewAuxiliar($viewName, $model, $label)
    {
        $this->addListView($viewName, $model, $label, 'fas fa-users');
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'btnDelete', false);
    }
}
