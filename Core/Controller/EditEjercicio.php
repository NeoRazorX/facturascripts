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

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Base\ExtendedController;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Model;

use FacturaScripts\Core\Model\GrupoEpigrafes;
use FacturaScripts\Core\Model\Epigrafe;
use FacturaScripts\Core\Model\Cuenta;

/**
 * Controlador para la edición de un registro del modelo Familia
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 * @author Raúl JIménez <comercial@nazcanetworks.com>
 */
class EditEjercicio extends ExtendedController\PanelController
{

    /**
     * Procedimiento para insertar vistas en el controlador
     */
    protected function createViews()
    {
        $this->addEditView('FacturaScripts\Core\Model\Ejercicio', 'EditEjercicio', 'exercise');
        $this->addListView('FacturaScripts\Core\Model\GrupoEpigrafes', 'EditEjercicioGrupoEpigrafes', 'Grupo epigrafe');
        $this->addListView('FacturaScripts\Core\Model\Epigrafe', 'EditEjercicioEpigrafe', 'Epigrafes');
        $this->addListView('FacturaScripts\Core\Model\Cuenta', 'EditEjercicioCuenta', 'account','fa-book');
        $this->addListView('FacturaScripts\Core\Model\SubCuenta', 'EditEjercicioSubCuenta', 'sub-account','fa-th-list fa-fw');
           
    }

    /**
     * Devuele el campo $fieldName del modelo Ejercicio
     *
     * @param string $fieldName
     *
     * @return string|boolean
     */
    private function getEjercicioFieldValue($fieldName)
    {
        $model = $this->views['EditEjercicio']->getModel();
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
            case 'EditEjercicio':
                $value = $this->request->get('code');
                $view->loadData($value);
                break;
            case 'EditEjercicioGrupoEpigrafes':
                $where = [new DataBase\DataBaseWhere('codejercicio', $this->getEjercicioFieldValue('codejercicio'))];
                $view->loadData($where);
                break;
           case 'EditEjercicioEpigrafe':
                $where = [new DataBase\DataBaseWhere('codejercicio', $this->getEjercicioFieldValue('codejercicio'))];
                $view->loadData($where);
               break;
            case 'EditEjercicioCuenta':
                $where = [new DataBase\DataBaseWhere('codejercicio', $this->getEjercicioFieldValue('codejercicio'))];
                $view->loadData($where);
                break;
            case 'EditEjercicioSubCuenta':
                $where = [new DataBase\DataBaseWhere('codejercicio', $this->getEjercicioFieldValue('codejercicio'))];
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
        $pagedata['title'] = 'exercise';
        $pagedata['menu'] = 'accounting';
        $pagedata['icon'] = 'fa-calendar';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
