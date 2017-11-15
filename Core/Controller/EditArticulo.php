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

/**
 * Controller to edit a single item from the EditArticulo model
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author Fco. Antonio Moreno Pérez <famphuelva@gmail.com>
 */
class EditArticulo extends ExtendedController\PanelController
{

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addEditView('FacturaScripts\Core\Model\Articulo', 'EditArticulo', 'products', 'fa-cubes');
        $this->addListView('FacturaScripts\Core\Model\Articulo', 'ListFabricante', 'same-suppliers', 'fa-users');
        $this->addListView('FacturaScripts\Core\Model\Articulo', 'ListFamilia', 'same-families', 'fa-object-group');
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
            case 'EditArticulo':
                $value = $this->request->get('code');
                $view->loadData($value);
                break;

            case 'ListFabricante':
                $codfabricante = $this->getViewModelValue('EditArticulo', 'codfabricante');

                if (!empty($codfabricante)) {
                    $where = [new DataBase\DataBaseWhere('codfabricante', $codfabricante)];
                    $view->loadData($where);
                }
                break;

            case 'ListFamilia':
                $codfamilia = $this->getArticuloFieldValue('codfamilia');

                if (!empty($codfamilia)) {
                    $where = [new DataBase\DataBaseWhere('codfamilia', $codfamilia)];
                    $view->loadData($where);
                }
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
        $pagedata['title'] = 'product';
        $pagedata['icon'] = 'fa-cube';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
