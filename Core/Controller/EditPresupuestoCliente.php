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
 * Controller to edit a single item from the PresupuestoCliente model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Fco. Antonio Moreno Pérez <famphuelva@gmail.com>
 */
class EditPresupuestoCliente extends ExtendedController\DocumentController
{

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->addEditView('\FacturaScripts\Dinamic\Model\PresupuestoCliente', 'EditPresupuestoCliente', 'detail');
    }

    /**
     * Return the document class name.
     *
     * @return string
     */
    protected function getDocumentClassName()
    {
        return '\FacturaScripts\Dinamic\Model\PresupuestoCliente';
    }

    /**
     * Return the document line class name.
     *
     * @return string
     */
    protected function getDocumentLineClassName()
    {
        return '\FacturaScripts\Dinamic\Model\LineaPresupuestoCliente';
    }

    /**
     * Load data view procedure
     *
     * @param string $keyView
     * @param ExtendedController\EditView $view
     */
    protected function loadData($keyView, $view)
    {
        $idpresupuesto = $this->request->get('code');
        if ($idpresupuesto !== null && $idpresupuesto !== '') {
            switch ($keyView) {
                case 'EditPresupuestoCliente':
                    $view->loadData($idpresupuesto);
                    break;
            }
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
        $pagedata['title'] = 'estimation';
        $pagedata['menu'] = 'sales';
        $pagedata['icon'] = 'fa-files-o';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
