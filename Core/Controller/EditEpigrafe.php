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
 * Controlador para la edición de un registro del modelo Fabricante
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author PC REDNET S.L. <luismi@pcrednet.com>
 */
class EditEpigrafe extends ExtendedController\PanelController
{

    /**
     * Procedimiento para insertar vistas en el controlador
     */
    protected function createViews()
    {
        $this->addEditView('FacturaScripts\Core\Model\Epigrafe', 'EditEpigrafe', 'accounting-heading');
        $this->addListView('FacturaScripts\Core\Model\Cuenta', 'ListCuenta', 'accounts', 'fa-book');
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
            case 'EditEpigrafe':
                $value = $this->request->get('code');
                $view->loadData($value);
                break;

            case 'ListCuenta':
                $idepigrafe = $this->getViewModelValue('EditEpigrafe', 'idepigrafe');
                if (!empty($idepigrafe)) {
                    $where = [new DataBase\DataBaseWhere('idepigrafe', $idepigrafe)];
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
        $pagedata['title'] = 'accounting-heading';
        $pagedata['menu'] = 'accounting';
        $pagedata['icon'] = 'fa-bar-chart';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
