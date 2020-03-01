<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Lib\Accounting\Ledger;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\Ejercicio;

/**
 * Controller to edit a single item from the Cuenta model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Artex Trading sa             <jcuello@artextrading.com>
 * @author PC REDNET S.L.               <luismi@pcrednet.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class EditCuenta extends EditController
{

    /**
     * Returns the class name of the model to use in the editView.
     *
     * @return string
     */
    public function getModelClassName()
    {
        return 'Cuenta';
    }

    /**
     * Returns basic page attributes.
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'account';
        $data['icon'] = 'fas fa-book';
        return $data;
    }

    /**
     * Add subaccount ledger report and export.
     * - Add button
     * - Add export options
     * - Set initial values to modal form
     *
     * @param string $viewName
     */
    protected function addLedgerReport($viewName)
    {
        $this->addButton($viewName, Ledger::getButton('modal'));
        $this->setLedgerReportExportOptions($viewName);
        $this->setLedgerReportValues($viewName);
    }

    /**
     *
     * @param string $viewName
     */
    protected function createAccountingView($viewName = 'ListCuenta')
    {
        $this->addListView($viewName, 'Cuenta', 'children-accounts', 'fas fa-level-down-alt');
        $this->views[$viewName]->addOrderBy(['codcuenta'], 'code', 1);

        /// disable columns
        $this->views[$viewName]->disableColumn('fiscal-exercise');
        $this->views[$viewName]->disableColumn('parent-account');
    }

    /**
     *
     * @param string $viewName
     */
    protected function createSubAccountingView($viewName = 'ListSubcuenta')
    {
        $this->addListView($viewName, 'Subcuenta', 'subaccounts');
        $this->views[$viewName]->addOrderBy(['codsubcuenta'], 'code', 1);
        $this->views[$viewName]->addOrderBy(['saldo'], 'balance');

        /// disable columns
        $this->views[$viewName]->disableColumn('fiscal-exercise');
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->createSubAccountingView();
        $this->createAccountingView();
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'ledger':
                $code = $this->request->query->get('code');
                if (!empty($code)) {
                    $this->setTemplate(false);
                    $this->ledgerReport($code);
                }
                return true;

            default:
                return parent::execPreviousAction($action);
        }
    }

    /**
     * Exec ledger report from post/get values
     *
     * @param int $idAccount
     */
    protected function ledgerReport($idAccount)
    {
        $account = new Cuenta();
        $account->loadFromCode($idAccount);

        $request = $this->request->request->all();
        $params = [
            'grouped' => ('YES' == $request['grouped']),
            'channel' => $request['channel'],
            'account-from' => $account->codcuenta
        ];

        $ledger = new Ledger();
        $pages = $ledger->generate($request['dateFrom'], $request['dateTo'], $params);
        $this->exportManager->newDoc($request['format']);
        foreach ($pages as $data) {
            $headers = empty($data) ? [] : array_keys($data[0]);
            $this->exportManager->addTablePage($headers, $data);
        }
        $this->exportManager->show($this->response);
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
        $idcuenta = $this->getViewModelValue($mainViewName, 'idcuenta');

        switch ($viewName) {
            case 'ListCuenta':
                $where = [new DataBaseWhere('parent_idcuenta', $idcuenta)];
                $view->loadData('', $where);
                break;

            case 'ListSubcuenta':
                $where = [new DataBaseWhere('idcuenta', $idcuenta)];
                $view->loadData('', $where);
                if ($view->count > 0) {
                    $this->addLedgerReport($mainViewName);
                }
                break;

            case $mainViewName:
                parent::loadData($viewName, $view);
                if (!$view->model->exists()) {
                    $this->prepareCuenta($view);
                }
                break;
        }
    }

    /**
     *
     * @param BaseView $view
     */
    protected function prepareCuenta($view)
    {
        $cuenta = new Cuenta();
        $idcuenta = $this->request->query->get('parent_idcuenta', '');
        if (!empty($idcuenta) && $cuenta->loadFromCode($idcuenta)) {
            $view->model->codejercicio = $cuenta->codejercicio;
        }
    }

    /**
     * Set export options to widget of modal form
     *
     * @param string $viewName
     */
    private function setLedgerReportExportOptions($viewName)
    {
        $columnFormat = $this->views[$viewName]->columnModalForName('format');
        if (isset($columnFormat)) {
            $values = [];
            foreach ($this->exportManager->options() as $key => $options) {
                $values[] = ['title' => $options['description'], 'value' => $key];
            }
            $columnFormat->widget->setValuesFromArray($values, true);
        }
    }

    /**
     * Set initial values to modal fields
     *
     * @param string $viewName
     */
    private function setLedgerReportValues($viewName)
    {
        $codeExercise = $this->getViewModelValue($viewName, 'codejercicio');
        $exercise = new Ejercicio();
        $exercise->loadFromCode($codeExercise);

        $model = $this->views[$viewName]->model;
        $model->dateFrom = $exercise->fechainicio;
        $model->dateTo = $exercise->fechafin;
    }
}
