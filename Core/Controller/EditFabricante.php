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

use FacturaScripts\Core\Base\ExtendedController;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Model\Articulo;
/**
 * Controller to edit a single item from the Fabricante model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditFabricante extends ExtendedController\PanelController
{

    /**
     * Returns the model name
     */
    public function getModelName()
    {
        return 'FacturaScripts\Core\Model\Fabricante';
    }
    /**
     * Load views.
     */
    protected function createViews()
    {
        $this->addEditView('FacturaScripts\Core\Model\Fabricante', 'EditFabricante', 'manufacturer');
        $this->addListView('FacturaScripts\Core\Model\Articulo', 'EditFabricanteArticulos', 'manufacturer-products');
       
    }
    /**
     * Load view data procedure
     *
     * @param string $keyView
     * @param ExtendedController\EditView $view
     */
    protected function loadData($keyView, $view)
    {
        switch ($keyView) {
            case 'EditFabricante':
                $value = $this->request->get('code');
                $view->loadData($value);
                break;

            case 'EditFabricanteArticulos':
                $where = [new DataBase\DataBaseWhere('codfabricante', $this->request->get('code'))];
                $view->loadData($where);
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
        $pagedata['title'] = 'manufacturer';
        $pagedata['menu'] = 'warehouse';
        $pagedata['icon'] = 'fa-folder-open';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
