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
 * Controller to edit a single item from the AlbaranCliente model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class EditAlbaranCliente extends ExtendedController\DocumentController
{

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->addEditView('FacturaScripts\Core\Model\AlbaranCliente', 'EditAlbaranCliente', 'detail', 'fa-edit');
    }

    protected function getDocumentClassName()
    {
        return 'FacturaScripts\Core\Model\AlbaranCliente';
    }

    protected function getDocumentLineClassName()
    {
        return 'FacturaScripts\Core\Model\LineaAlbaranCliente';
    }

    /**
     * Load data view procedure
     *
     * @param string $keyView
     * @param ExtendedController\EditView $view
     */
    protected function loadData($keyView, $view)
    {
        $idalbaran = $this->request->get('code');

        switch ($keyView) {
            case 'EditAlbaranCliente':
                $view->loadData($idalbaran);
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
        $pagedata['title'] = 'delivery-note';
        $pagedata['menu'] = 'sales';
        $pagedata['icon'] = 'fa-files-o';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
