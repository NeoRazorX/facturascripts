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
 * Description of PanelUser
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class PanelUser extends ExtendedController\PanelController
{

    /**
     * Procedimiento para insertar vistas en el controlador
     */
    protected function createViews()
    {
        $this->addEditView('FacturaScripts\Core\Model\User', 'EditUser', 'user', 'fa-user');

        $this->addEditListView('FacturaScripts\Core\Model\RolUser', 'EditRolUser', 'rol-user', 'fa-address-card-o');
        $this->views['EditRolUser']->disableColumn('nick', TRUE);

        $this->addListView('FacturaScripts\Core\Model\PageRule', 'ListPageRule', 'page-rule', 'fa fa-check-square');
        $this->views['ListPageRule']->disableColumn('nick', TRUE);
    }

    /**
     * Devuele el campo $fieldName del modelo User
     *
     * @param string $fieldName
     *
     * @return string|boolean
     */
    private function getUserFieldValue($fieldName)
    {
        $model = $this->views['EditUser']->getModel();
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
            case 'EditUser':
                $value = $this->request->get('code');
                $view->loadData($value);
                break;

            case 'EditRolUser':
                $where = [new DataBase\DataBaseWhere('nick', $this->getUserFieldValue('nick'))];
                $view->loadData($where);
                break;

            case 'ListPageRule':
                $where = [new DataBase\DataBaseWhere('nick', $this->getUserFieldValue('nick'))];
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
        $pagedata['title'] = 'users';
        $pagedata['icon'] = 'fa-users';
        $pagedata['menu'] = 'admin';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
