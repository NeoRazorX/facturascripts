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
 * Description of EditRol
 *
 *
 * @author Artex Trading sa <jferrer@artextrading.com>
 */
class EditRol extends ExtendedController\PanelController
{

    /**
     * Procedimiento para insertar vistas en el controlador
     */
    protected function createViews()
    {
        $this->addEditView('FacturaScripts\Core\Model\Rol', 'EditRol', 'rol', 'fa-id-card');

        $this->addEditListView('FacturaScripts\Core\Model\RolUser', 'EditRolUser', 'rol-user', 'fa-address-card-o');
        $this->views['EditRolUser']->disableColumn('role', TRUE);

        $this->addListView('FacturaScripts\Core\Model\RolAccess', 'ListRolAccess', 'page-rule', 'fa fa-check-square');
        $this->views['ListRolAccess']->disableColumn('role', TRUE);
    }

    /**
     * Devuele el campo $fieldName del modelo Rol
     *
     * @param string $fieldName
     *
     * @return string|boolean
     */
    private function getRolFieldValue($fieldName)
    {
        $model = $this->views['EditRol']->getModel();
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
            case 'EditRol':
                $value = $this->request->get('code');
                $view->loadData($value);
                break;

            case 'EditRolUser':
                $where = [new DataBase\DataBaseWhere('codrol', $this->getRolFieldValue('codrol'))];
                $view->loadData($where);
                break;

            case 'ListRolAccess':
                $where = [new DataBase\DataBaseWhere('codrol', $this->getRolFieldValue('codrol'))];
                $view->loadData($where);
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
        $pagedata['title'] = 'rol';
        $pagedata['menu'] = 'admin';
        $pagedata['icon'] = 'fa-id-card-o';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
