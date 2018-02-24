<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ExtendedController;

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
        $this->views['Document']->documentType = 'purchase';
        $this->addEditView($this->getDocumentClassName(), 'EditPedidoProveedor', 'detail', 'fa-edit');
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
     * @param string                      $keyView
     * @param ExtendedController\EditView $view
     */
    protected function loadData($keyView, $view)
    {
        if ($keyView === 'EditPedidoProveedor') {
            $idpedido = $this->getViewModelValue('Document', 'idpedido');
            $view->loadData($idpedido);
        }

        parent::loadData($keyView, $view);
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'order';
        $pagedata['menu'] = 'purchases';
        $pagedata['icon'] = 'fa-files-o';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
