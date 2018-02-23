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
 * Controller to edit a single item from the Fabricante model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditFabricante extends ExtendedController\PanelController
{

    /**
     * Load views.
     */
    protected function createViews()
    {
        $this->addEditView('\FacturaScripts\Dinamic\Model\Fabricante', 'EditFabricante', 'manufacturer');
        $this->addListView('\FacturaScripts\Dinamic\Model\Articulo', 'EditFabricanteListArticulos', 'products');
    }

    /**
     * Load data view procedure
     *
     * @param string                      $keyView
     * @param ExtendedController\BaseView $view
     */
    protected function loadData($keyView, $view)
    {
        switch ($keyView) {
            case 'EditFabricante':
                $code = $this->request->get('code');
                $view->loadData($code);
                break;

            case 'EditFabricanteListArticulos':
                $codfabricante = $this->getViewModelValue('EditFabricante', 'codfabricante');
                $where = [new DataBaseWhere('codfabricante', $codfabricante)];
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
        $pagedata['title'] = 'manufacturer';
        $pagedata['menu'] = 'warehouse';
        $pagedata['icon'] = 'fa-folder-open';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
