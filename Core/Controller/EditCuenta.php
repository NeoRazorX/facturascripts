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
use FacturaScripts\Dinamic\Lib\Accounting\Ledger;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\Ejercicio;

/**
 * Controller to edit a single item from the Cuenta model
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author PC REDNET S.L.                <luismi@pcrednet.com>
 * @author Cristo M. Estévez Hernández   <cristom.estevez@gmail.com>
 */
class EditCuenta extends EditController
{
    public function getModelClassName(): string
    {
        return 'Cuenta';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'account';
        $data['icon'] = 'fas fa-book';
        return $data;
    }

    protected function createAccountingView(string $viewName = 'ListCuenta')
    {
        $this->addListView($viewName, 'Cuenta', 'children-accounts', 'fas fa-level-down-alt');
        $this->views[$viewName]->addOrderBy(['codcuenta'], 'code', 1);

        // disable columns
        $this->views[$viewName]->disableColumn('fiscal-exercise');
        $this->views[$viewName]->disableColumn('parent-account');
    }

    protected function createSubAccountingView(string $viewName = 'ListSubcuenta')
    {
        $this->addListView($viewName, 'Subcuenta', 'subaccounts');
        $this->views[$viewName]->addOrderBy(['codsubcuenta'], 'code', 1);
        $this->views[$viewName]->addOrderBy(['descripcion'], 'description');
        $this->views[$viewName]->addOrderBy(['debe'], 'debit');
        $this->views[$viewName]->addOrderBy(['haber'], 'credit');
        $this->views[$viewName]->addOrderBy(['saldo'], 'balance');
        $this->views[$viewName]->addSearchFields(['codsubcuenta', 'descripcion']);

        // disable columns
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
        if ($action == 'ledger') {
            if (false === $this->permissions->allowExport) {
                $this->toolBox()->i18nLog()->warning('no-print-permission');
                return true;
            }

            $code = $this->request->query->get('code');
            if (!empty($code)) {
                $this->setTemplate(false);
                $this->ledgerReport($code);
            }
            return true;
        }

        return parent::execPreviousAction($action);
    }

    protected function ledgerReport(int $idAccount)
    {
        $account = new Cuenta();
        $account->loadFromCode($idAccount);
        $request = $this->request->request->all();

        $ledger = new Ledger();
        $pages = $ledger->generate($account->getExercise()->idempresa, $request['dateFrom'], $request['dateTo'], [
            'channel' => $request['channel'],
            'format' => $request['format'],
            'grouped' => $request['groupingtype'],
            'account-from' => $account->codcuenta
        ]);
        $title = self::toolBox()::i18n()->trans('ledger') . ' ' . $account->codcuenta;
        $this->exportManager->newDoc($request['format'], $title);

        // añadimos la tabla de cabecera con la info del informe
        if ($request['format'] === 'PDF') {
            $titles = [[
                self::toolBox()::i18n()->trans('account') => $account->codcuenta,
                self::toolBox()::i18n()->trans('exercise') => $account->codejercicio,
                self::toolBox()::i18n()->trans('from-date') => $request['dateFrom'],
                self::toolBox()::i18n()->trans('until-date') => $request['dateTo']
            ]];
            $this->exportManager->addTablePage(array_keys($titles[0]), $titles);
        }

        // tablas con los listados
        $options = [
            'debe' => ['display' => 'right', 'css' => 'nowrap'],
            'haber' => ['display' => 'right', 'css' => 'nowrap'],
            'saldo' => ['display' => 'right', 'css' => 'nowrap'],
        ];
        foreach ($pages as $data) {
            $headers = empty($data) ? [] : array_keys($data[0]);
            $this->exportManager->addTablePage($headers, $data, $options);
        }
        $this->exportManager->show($this->response);
    }

    /**
     * Load view data procedure
     *
     * @param string $viewName
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
                    $this->addButton($mainViewName, [
                        'action' => 'ledger',
                        'color' => 'info',
                        'icon' => 'fas fa-book fa-fw',
                        'label' => 'ledger',
                        'type' => 'modal'
                    ]);
                    $this->setLedgerReportExportOptions($mainViewName);
                    $this->setLedgerReportValues($mainViewName);
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

    protected function prepareCuenta(BaseView $view)
    {
        $cuenta = new Cuenta();
        $idcuenta = $this->request->query->get('parent_idcuenta', '');
        if (!empty($idcuenta) && $cuenta->loadFromCode($idcuenta)) {
            $view->model->codejercicio = $cuenta->codejercicio;
        }
    }

    private function setLedgerReportExportOptions(string $viewName)
    {
        $columnFormat = $this->views[$viewName]->columnModalForName('format');
        if ($columnFormat && $columnFormat->widget->getType() === 'select') {
            $values = [];
            foreach ($this->exportManager->options() as $key => $options) {
                $values[] = ['title' => $options['description'], 'value' => $key];
            }
            $columnFormat->widget->setValuesFromArray($values, true);
        }
    }

    private function setLedgerReportValues(string $viewName)
    {
        $codeExercise = $this->getViewModelValue($viewName, 'codejercicio');
        $exercise = new Ejercicio();
        $exercise->loadFromCode($codeExercise);

        $model = $this->views[$viewName]->model;
        $model->dateFrom = $exercise->fechainicio;
        $model->dateTo = $exercise->fechafin;
    }
}
