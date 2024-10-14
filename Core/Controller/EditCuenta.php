<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
        $data['icon'] = 'fa-solid fa-book';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();

        // desactivamos el botón de imprimir
        $mvn = $this->getMainViewName();
        $this->tab($mvn)->setSettings('btnPrint', false);

        // ponemos las pestañas en la parte inferior
        $this->setTabsPosition('bottom');

        // añadimos las vistas
        $this->createViewsSubAccounts();
        $this->createViewsChildAccounts();
    }

    protected function createViewsChildAccounts(string $viewName = 'ListCuenta'): void
    {
        $this->addListView($viewName, 'Cuenta', 'children-accounts', 'fa-solid fa-level-down-alt')
            ->addOrderBy(['codcuenta'], 'code', 1)
            ->disableColumn('fiscal-exercise')
            ->disableColumn('parent-account');
    }

    protected function createViewsSubAccounts(string $viewName = 'ListSubcuenta'): void
    {
        $this->addListView($viewName, 'Subcuenta', 'subaccounts')
            ->addOrderBy(['codsubcuenta'], 'code', 1)
            ->addOrderBy(['descripcion'], 'description')
            ->addOrderBy(['debe'], 'debit')
            ->addOrderBy(['haber'], 'credit')
            ->addOrderBy(['saldo'], 'balance')
            ->addSearchFields(['codsubcuenta', 'descripcion'])
            ->disableColumn('fiscal-exercise');
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
                Tools::log()->warning('no-print-permission');
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

    protected function ledgerReport(int $idAccount): void
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
        $title = Tools::lang()->trans('ledger') . ' ' . $account->codcuenta;
        $this->exportManager->newDoc($request['format'], $title);

        // añadimos la tabla de cabecera con la info del informe
        if ($request['format'] === 'PDF') {
            $titles = [[
                Tools::lang()->trans('account') => $account->codcuenta,
                Tools::lang()->trans('exercise') => $account->codejercicio,
                Tools::lang()->trans('from-date') => $request['dateFrom'],
                Tools::lang()->trans('until-date') => $request['dateTo']
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

                // ocultamos la columna saldo de los totales
                unset($view->totalAmounts['saldo']);
                break;

            case 'ListSubcuenta':
                $where = [new DataBaseWhere('idcuenta', $idcuenta)];
                $view->loadData('', $where);
                if ($view->count == 0) {
                    break;
                }

                // ocultamos la columna saldo de los totales
                unset($view->totalAmounts['saldo']);

                // añadimos botón de imprimir mayor
                $this->addButton($mainViewName, [
                    'action' => 'ledger',
                    'color' => 'info',
                    'icon' => 'fa-solid fa-print fa-fw',
                    'label' => 'print',
                    'type' => 'modal'
                ]);
                $this->setLedgerReportExportOptions($mainViewName);
                $this->setLedgerReportValues($mainViewName);
                break;

            case $mainViewName:
                parent::loadData($viewName, $view);
                if (!$view->model->exists()) {
                    $this->prepareCuenta($view);
                }
                break;
        }
    }

    protected function prepareCuenta(BaseView $view): void
    {
        $cuenta = new Cuenta();
        $idcuenta = $this->request->query->get('parent_idcuenta', '');
        if (!empty($idcuenta) && $cuenta->loadFromCode($idcuenta)) {
            $view->model->codejercicio = $cuenta->codejercicio;
        }
    }

    private function setLedgerReportExportOptions(string $viewName): void
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

    private function setLedgerReportValues(string $viewName): void
    {
        $codeExercise = $this->getViewModelValue($viewName, 'codejercicio');
        $exercise = new Ejercicio();
        $exercise->loadFromCode($codeExercise);

        $model = $this->views[$viewName]->model;
        $model->dateFrom = $exercise->fechainicio;
        $model->dateTo = $exercise->fechafin;
    }
}
