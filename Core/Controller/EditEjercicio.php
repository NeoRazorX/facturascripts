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
use FacturaScripts\Core\Model;

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
     * Devuelve un array de grupos de epigrafes, el resultado estará filtrado
     * por el ejercicio que se recibe como parámetro
     * 
     * @param integer exercise_code
     * @return array
     */
    private function getGroups($exercise_code)
    {
        $groups = [];
        $group_model = new GrupoEpigrafes();
        $obj_groups = $group_model->all([new DataBase\DataBaseWhere('codejercicio' , $exercise_code)]);   
        foreach ($obj_groups as $group_item) {
            $item['value'] = $group_item->idgrupo;
            $item['title'] = $group_item->descripcion;
            $groups[] = $item;
        }
        
        return $groups;
    }
    /**
     * Devuelve un array de epigrafes, el resultado estará filtrado
     * por el ejercicio que se recibe como parámetro
     * 
     * @param integer exercise_code
     * @return array
     */
    private function getEpigrafes($exercise_code)
    {
        $epigrafes = [];
        $epigrafe_model = new Epigrafe();
        $obj_epigrafes = $epigrafe_model->all([new DataBase\DataBaseWhere('codejercicio' , $exercise_code)]);
        foreach ($obj_epigrafes as $epigrafe_item) {
            $item['value'] = $epigrafe_item->idepigrafe;
            $item['title'] = $epigrafe_item->descripcion;
            $epigrafes[] = $item;
        }
        
        return $epigrafes;
    }
    
    /**
     * Devuelve un array de Cuentas, el resultado estará filtrado
     * por el ejercicio que se recibe como parámetro
     * 
     * @param integer exercise_code
     * @return array
     */
    private function getCuentas($exercise_code)
    {
        $cuentas = [];
        $cuentas_model = new Cuenta();

        $obj_cuentas = $cuentas_model->all([new DataBase\DataBaseWhere('codejercicio' , $exercise_code)]);   
      
        foreach ($obj_cuentas as $cuenta_item) {
          
            $item['value'] = $cuenta_item -> idcuenta;
            $item['title'] = $cuenta_item -> descripcion;
            $cuentas[] = $item;
   
        }
       
        return $cuentas;
    }
    /**
     * Procedimiento para insertar vistas en el controlador
     */
    protected function createViews()
    {
        $this->addEditView('FacturaScripts\Core\Model\Ejercicio', 'EditEjercicio', 'exercise');
        $this->addEditListView('FacturaScripts\Core\Model\GrupoEpigrafes', 'EditEjercicioGrupoEpigrafes', 'Grupo epigrafe');
        $this->addEditListView('FacturaScripts\Core\Model\Epigrafe', 'EditEjercicioEpigrafe', 'Epigrafes');
        $this->addEditListView('FacturaScripts\Core\Model\Cuenta', 'EditEjercicioCuenta', 'account','fa-book');
        $this->addEditListView('FacturaScripts\Core\Model\SubCuenta', 'EditEjercicioSubCuenta', 'sub-account','fa-th-list fa-fw');
        
         /// Cargamos valores para Los grupos de Epigrafes para EditEjercicioEpigrafe
        $columnGroupCode = $this->views['EditEjercicioEpigrafe']->columnForName('heading-account-group-id');
        $groups = $this->getGroups($this->request->get('code'));
        $columnGroupCode->widget->setValuesFromArray($groups);
        
        /// Cargamos los valores par los Epigrafs para la vista Cuentas filtrando por ejercicio
        $columnEpigrafeCode = $this->views['EditEjercicioCuenta']->columnForName('accounting-heading');
        $epigrafes = $this->getEpigrafes($this->request->get('code'));
        $columnEpigrafeCode->widget->setValuesFromArray($epigrafes);
        
        /// Cargamos los valores para las Cuetnas para la vista SubCuentas filtrando por ejercicio
      $columnCuentaCode = $this->views['EditEjercicioSubCuenta']->columnForName('account-id');
        $cuentas = $this->getCuentas($this->request->get('code'));
        $columnCuentaCode->widget->setValuesFromArray($cuentas);
       
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
