<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Dinamic\Model\Cuenta;

/**
 * Controller to edit a single item from the SubCuenta model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Artex Trading sa             <jcuello@artextrading.com>
 * @author PC REDNET S.L.               <luismi@pcrednet.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class EditSubcuenta extends EditController
{

    /**
     * Returns the class name of the model to use in the editView.
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'Subcuenta';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'subaccount';
        $data['icon'] = 'fas fa-th-list';
        return $data;
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createDepartureView($viewName = 'ListPartidaAsiento')
    {
        $this->addListView($viewName, 'ModelView\PartidaAsiento', 'accounting-entries', 'fas fa-balance-scale');
        $this->views[$viewName]->searchFields[] = 'concepto';
        $this->views[$viewName]->addOrderBy(['fecha', 'numero'], 'date', 2);

        /// disable column
        $this->views[$viewName]->disableColumn('subaccount');
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->createDepartureView();
    }

    /**
     * Load view data procedure
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->getMainViewName();

        switch ($viewName) {
            case 'ListPartidaAsiento':
                $idsubcuenta = $this->getViewModelValue($this->getMainViewName(), 'idsubcuenta');
                $where = [new DataBaseWhere('idsubcuenta', $idsubcuenta)];
                $view->loadData('', $where);
                break;

            case $mainViewName:
                parent::loadData($viewName, $view);
                if (!$view->model->exists()) {
                    $this->prepareSubcuenta($view);
                }
                break;
        }
    }

    /**
     * 
     * @param BaseView $view
     */
    protected function prepareSubcuenta($view)
    {
        $cuenta = new Cuenta();
        $idcuenta = $this->request->query->get('idcuenta', '');
        if (!empty($idcuenta) && $cuenta->loadFromCode($idcuenta)) {
            $view->model->codcuenta = $cuenta->codcuenta;
            $view->model->codejercicio = $cuenta->codejercicio;
            $view->model->idcuenta = $cuenta->idcuenta;
        }
    }
}
