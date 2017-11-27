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
use FacturaScripts\Core\Base\ExtendedController;
use FacturaScripts\Core\Model;

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
        $this->addEditView(Model\Articulo::class, 'EditArticulo', 'products', 'fa-cubes');
        $this->addEditListView(Model\Stock::class, 'EditStock', 'stock');
        $this->addListView(Model\ArticuloProveedor::class, 'ListArticuloProveedor', 'suppliers', 'fa-ship');
        $this->addListView(Model\ArticuloCombinacion::class, 'ListArticuloCombinacion', 'combinations', 'fa-sliders');
        $this->addListView(Model\ArticuloTraza::class, 'ListArticuloTraza', 'traceability', 'fa-barcode');
    }

    /**
     * Load view data procedure
     *
     * @param string $keyView
     * @param ExtendedController\EditView $view
     */
    protected function loadData($keyView, $view)
    {
        if ($this->getViewModelValue('EditArticulo', 'secompra') === false) {
            unset($this->views['ListArticuloProveedor']);
        }

        if ($this->getViewModelValue('EditArticulo', 'tipo') !== 'atributos') {
            unset($this->views['ListArticuloCombinacion']);
        }

        if ($this->getViewModelValue('EditArticulo', 'trazabilidad') === false) {
            unset($this->views['ListArticuloTraza']);
        }

        $referencia = $this->request->get('code');
        switch ($keyView) {
            case 'EditArticulo':
                $view->loadData($referencia);
                break;

            case 'EditStock':
            case 'ListArticuloProveedor':
            case 'ListArticuloCombinacion':
            case 'ListArticuloTraza':
                $where = [new DataBaseWhere('referencia', $referencia)];
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
        $pagedata['title'] = 'product';
        $pagedata['icon'] = 'fa-cube';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
