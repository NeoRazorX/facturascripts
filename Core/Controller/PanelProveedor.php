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
 * Description of PanelSettings
 *
 * @author Nazca Networks <comercial@nazcanetworks.com>
 */
class PanelProveedor extends ExtendedController\PanelController
{
    /**
     * Procedimiento para insertar vistas en el controlador
     */
    protected function createViews()
    {
        $this->addEditView('FacturaScripts\Core\Model\Proveedor', 'EditProveedor', 'supplier');
        $this->addEditListView('FacturaScripts\Core\Model\DireccionProveedor', 'EditDireccionProveedor', 'addresses', 'fa-road');
       /* $this->addListView('FacturaScripts\Core\Model\Proveedor', 'ListProveedor', 'same-group');*/
    }

    /**
     * Devuele el campo $fieldName del cliente
     *
     * @param string $fieldName
     *
     * @return mixed
     */
    private function getProviderFieldValue($fieldName)
    {
        $model = $this->views['EditProveedor']->getModel();
        return $model->{$fieldName};
    }

    /**
     * Procedimiento encargado de cargar los datos a visualizar
     *
     * @param string $keyView
     * @param ExtendedController\EditView $view
     */
    protected function loadData($keyView, $view)
    {
        switch ($keyView) {
            case 'EditProveedor':
                $value = $this->request->get('code');
                $view->loadData($value);
                break;

            case 'EditDireccionProveedor':
                $where = [new DataBase\DataBaseWhere('codproveedor', $this->getProviderFieldValue('codproveedor'))];
                $view->loadData($where);
                break;
            
            case 'ListProveedor':
                $codgroup = $this->getClientFieldValue('codgrupo');

                if (!empty($codgroup)) {
                    $where = [new DataBase\DataBaseWhere('codgrupo', $codgroup)];
                    $view->loadData($where);
                }
                break;
        }
    }

    /**
     * Devuelve los datos básicos de la página
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'suppliers';
        $pagedata['icon'] = 'fa-users';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
