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

/**
 * Controller to edit a single item from the PedidoProveedor model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @autor Luis Miguel Pérez <luismi@pcrednet.com>
 */
class EditPedidoProveedor extends ExtendedController\DocumentController
{

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->addEditView('\FacturaScripts\Dinamic\Model\PedidoProveedor', 'EditPedidoProveedor', 'detail', 'fa-edit');
    }

    /**
     * Return the document class name.
     *
     * @return string
     */
    protected function getDocumentClassName()
    {
        return '\FacturaScripts\Dinamic\Model\PedidoProveedor';
    }

    /**
     * Return the document line class name.
     *
     * @return string
     */
    protected function getDocumentLineClassName()
    {
        return '\FacturaScripts\Dinamic\Model\LineaPedidoProveedor';
    }

    /**
     * Load data view procedure
     *
     * @param string $keyView
     * @param ExtendedController\EditView $view
     */
    protected function loadData($keyView, $view)
    {
        $idpedidoproveedor = $this->request->get('code');

        switch ($keyView) {
            case 'EditPedidoProveedor':
                $view->loadData($idpedidoproveedor);
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
        $pagedata['title'] = 'supplier-order';
        $pagedata['menu'] = 'purchases';
        $pagedata['icon'] = 'fa-files-o';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
