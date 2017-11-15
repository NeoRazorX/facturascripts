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
use FacturaScripts\Core\Model\GrupoEpigrafes;
use FacturaScripts\Core\Model\Epigrafe;
use FacturaScripts\Core\Model\Cuenta;

/**
 * Controller to edit a single item from the Familia model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class EditEjercicio extends ExtendedController\PanelController
{

    /**
     * Load views.
     */
    protected function createViews()
    {
        $this->addEditView('FacturaScripts\Core\Model\Ejercicio', 'EditEjercicio', 'exercise');
        $this->addEditListView('FacturaScripts\Core\Model\GrupoEpigrafes', 'EditEjercicioGrupoEpigrafes', 'Grupo epigrafe');
        $this->addEditListView('FacturaScripts\Core\Model\Epigrafe', 'EditEjercicioEpigrafe', 'Epigrafes');
        $this->addEditListView('FacturaScripts\Core\Model\Cuenta', 'EditEjercicioCuenta', 'account', 'fa-book');
        $this->addEditListView('FacturaScripts\Core\Model\Subcuenta', 'EditEjercicioSubcuenta', 'subaccount');
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
            case 'EditEjercicio':
                $value = $this->request->get('code');
                $view->loadData($value);
                break;

            case 'EditEjercicioGrupoEpigrafes':
                $where = [new DataBase\DataBaseWhere('codejercicio', $this->request->get('code'))];
                $view->loadData($where);
                break;

            case 'EditEjercicioEpigrafe':
                $where = [new DataBase\DataBaseWhere('codejercicio', $this->request->get('code'))];
                $view->loadData($where);
                break;

            case 'EditEjercicioCuenta':
                $where = [new DataBase\DataBaseWhere('codejercicio', $this->request->get('code'))];
                $view->loadData($where);
                break;

            case 'EditEjercicioSubcuenta':
                $where = [new DataBase\DataBaseWhere('codejercicio', $this->request->get('code'))];
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
        $pagedata['title'] = 'exercise';
        $pagedata['menu'] = 'accounting';
        $pagedata['icon'] = 'fa-calendar';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
