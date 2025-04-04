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
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\RegimenIVA;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\SettingsModel;

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
        $data['icon'] = 'fa-solid fa-building';
        return $data;
    }

    protected function checkViesAction(): bool
    {
        $model = $this->getModel();
        if (false === $model->loadFromCode($this->request->get('code'))) {
            return true;
        }

        if ($model->checkVies()) {
            Tools::log()->notice('vies-check-success', ['%vat-number%' => $model->cifnif]);
        }

        return true;
    }

    protected function createViews()
    {
        parent::createViews();
        $this->createViewWarehouse();
        $this->createViewBankAccounts();
        $this->createViewPaymentMethods();
        $this->createViewExercises();
        $this->createViewSettings();
    }

    protected function createViewBankAccounts(string $viewName = 'ListCuentaBanco'): void
    {
        $this->addListView($viewName, 'CuentaBanco', 'bank-accounts', 'fa-solid fa-piggy-bank')
            ->disableColumn('company');
    }

    protected function createViewExercises(string $viewName = 'ListEjercicio'): void
    {
        $this->addListView($viewName, 'Ejercicio', 'exercises', 'fa-solid fa-calendar-alt')
            ->disableColumn('company');
    }

    protected function createViewPaymentMethods(string $viewName = 'ListFormaPago'): void
    {
        $this->addListView($viewName, 'FormaPago', 'payment-method', 'fa-solid fa-credit-card')
            ->disableColumn('company');
    }

    protected function createViewWarehouse(string $viewName = 'EditAlmacen'): void
    {
        $this->addListView($viewName, 'Almacen', 'warehouses', 'fa-solid fa-warehouse')
            ->disableColumn('company');
    }

    protected function createViewSettings(string $viewName = 'EditSettingsModel'): void
    {
        $this->addEditView($viewName, 'SettingsModel', 'settings', 'fas fa-wrench');
    }

    protected function execPreviousAction($action): bool
    {
        if ($this->active === 'EditSettingsModel' && in_array($action, ['edit', 'insert'])){

            /** @var Empresa $empresa */
            $empresa = $this->getModel();
            $empresa->loadFromCode($this->request->get('code'));
            $this->request->request->set('idempresa', $empresa->primaryColumnValue());

            /** @var SettingsModel $settingsModel */
            $settingsModel = $this->views[$this->active]->model;
            $settingsModel->loadFromCode('', [
                new DataBaseWhere('classnamemodel', get_class($empresa)),
                new DataBaseWhere('idmodel', $empresa->primaryColumnValue()),
                new DataBaseWhere('idempresa', $empresa->primaryColumnValue()),
            ]);

            $settingsModel->classnamemodel = get_class($empresa);
            $settingsModel->idmodel = $empresa->primaryColumnValue();
            $settingsModel->idempresa = $empresa->primaryColumnValue();
            $settingsModel->settings = json_encode([
                'codalmacen' => empty($this->request->request->get('codalmacen')) ? null : $this->request->request->get('codalmacen'),
                'codserie' => empty($this->request->request->get('codserie')) ? null : $this->request->request->get('codserie'),
                'coddivisa' => empty($this->request->request->get('coddivisa')) ? null : $this->request->request->get('coddivisa'),
                'codpago' => empty($this->request->request->get('codpago')) ? null : $this->request->request->get('codpago'),
            ]);
        }

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
            case 'EditSettingsModel':
                /** @var Empresa $empresa */
                $empresa = $this->getModel();
                $empresa->loadFromCode($this->request->get('code'));

                /** @var SettingsModel $settingsModel */
                $settingsModel = $this->views[$viewName]->model;
                $where = [
                    new DataBaseWhere('classnamemodel', get_class($empresa)),
                    new DataBaseWhere('idempresa', $empresa->idempresa),
                ];
                $settingsModel->loadFromCode('', $where);

                if($settingsModel->exists()){
                    // agregamos al modelo las propiedades que hay dentro de settings
                    // para conservar la clase con todas las propiedades y que detecte si el modelo existe o no.
                    foreach ($this->views[$viewName]->model->settings as $key => $value) {
                        $this->views[$viewName]->model->{$key} = $value;
                    }
                }

                break;

            case $mvn:
                parent::loadData($viewName, $view);
                $this->setCustomWidgetValues($view);
                if ($view->model->exists() && $view->model->cifnif) {
                    $this->addButton($viewName, [
                        'action' => 'check-vies',
                        'color' => 'info',
                        'icon' => 'fa-solid fa-check-double',
                        'label' => 'check-vies'
                    ]);
                }
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    protected function setCustomWidgetValues(BaseView &$view): void
    {
        $columnVATType = $view->columnForName('vat-regime');
        if ($columnVATType && $columnVATType->widget->getType() === 'select') {
            $columnVATType->widget->setValuesFromArrayKeys(RegimenIVA::all(), true);
        }

        $columnVATException = $view->columnForName('vat-exception');
        if ($columnVATException && $columnVATException->widget->getType() === 'select') {
            $columnVATException->widget->setValuesFromArrayKeys(RegimenIVA::allExceptions(), true, true);
        }
    }
}
