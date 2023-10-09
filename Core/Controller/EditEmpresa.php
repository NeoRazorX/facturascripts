<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Lib\RegimenIVA;

/**
 * Controller to edit a single item from the  Empresa model
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Daniel Fernández Giménez      <hola@danielfg.es>
 */
class EditEmpresa extends EditController
{
    public function getModelClassName(): string
    {
        return 'Empresa';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'company';
        $data['icon'] = 'fas fa-building';
        return $data;
    }

    protected function checkViesAction(): bool
    {
        $model = $this->getModel();
        if (false === $model->loadFromCode($this->request->get('code'))) {
            return true;
        }

        $model->checkVies();
        return true;
    }

    protected function createViews()
    {
        parent::createViews();
        $this->createViewWarehouse();
        $this->createViewBankAccounts();
        $this->createViewPaymentMethods();
        $this->createViewExercises();
    }

    protected function createViewBankAccounts(string $viewName = 'ListCuentaBanco')
    {
        $this->addListView($viewName, 'CuentaBanco', 'bank-accounts', 'fas fa-piggy-bank');
        $this->views[$viewName]->disableColumn('company');
    }

    protected function createViewExercises(string $viewName = 'ListEjercicio')
    {
        $this->addListView($viewName, 'Ejercicio', 'exercises', 'fas fa-calendar-alt');
        $this->views[$viewName]->disableColumn('company');
    }

    protected function createViewPaymentMethods(string $viewName = 'ListFormaPago')
    {
        $this->addListView($viewName, 'FormaPago', 'payment-method', 'fas fa-credit-card');
        $this->views[$viewName]->disableColumn('company');
    }

    protected function createViewWarehouse(string $viewName = 'EditAlmacen')
    {
        $this->addListView($viewName, 'Almacen', 'warehouses', 'fas fa-warehouse');
        $this->views[$viewName]->disableColumn('company');
    }

    protected function execPreviousAction($action): bool
    {
        switch ($action) {
            case 'check-vies':
                return $this->checkViesAction();

            default:
                return parent::execPreviousAction($action);
        }
    }

    /**
     * Load view data procedure
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mvn = $this->getMainViewName();

        switch ($viewName) {
            case 'EditAlmacen':
            case 'ListCuentaBanco':
            case 'ListEjercicio':
            case 'ListFormaPago':
                $id = $this->getViewModelValue($this->getMainViewName(), 'idempresa');
                $where = [new DataBaseWhere('idempresa', $id)];
                $view->loadData('', $where);
                break;

            case $mvn:
                parent::loadData($viewName, $view);
                $this->setCustomWidgetValues($view);
                if ($view->model->exists()) {
                    $this->addButton($viewName, [
                        'action' => 'check-vies',
                        'color' => 'info',
                        'icon' => 'fas fa-check-double',
                        'label' => 'check-vies',
                        'type' => 'action'
                    ]);
                }
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    protected function setCustomWidgetValues(BaseView &$view)
    {
        $columnVATType = $view->columnForName('vat-regime');
        if ($columnVATType && $columnVATType->widget->getType() === 'select') {
            $columnVATType->widget->setValuesFromArrayKeys(RegimenIVA::all(), true);
        }

        $columnVATException = $view->columnForName('vat-exception');
        if ($columnVATException && $columnVATException->widget->getType() === 'select') {
            $columnVATException->widget->setValuesFromArrayKeys(RegimenIVA::allExceptions(), true, true);
        }

        $columnLogo = $view->columnForName('logo');
        if ($columnLogo && $columnLogo->widget->getType() === 'select') {
            $images = $this->codeModel->all('attached_files', 'idfile', 'filename', true, [
                new DataBaseWhere('mimetype', 'image/gif,image/jpeg,image/png', 'IN')
            ]);
            $columnLogo->widget->setValuesFromCodeModel($images);
        }
    }
}
