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
use FacturaScripts\Dinamic\Model\Partida;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Controller to edit a single item from the SubCuenta model
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author PC REDNET S.L.                <luismi@pcrednet.com>
 * @author Cristo M. Estévez Hernández   <cristom.estevez@gmail.com>
 */
class EditSubcuenta extends EditController
{
    public function getModelClassName(): string
    {
        return 'Subcuenta';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'subaccount';
        $data['icon'] = 'fas fa-th-list';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        // añadimos las partidas de asientos
        $this->createViewsLines();
    }

    protected function createViewsLines(string $viewName = 'ListPartidaAsiento')
    {
        $this->addListView($viewName, 'Join\PartidaAsiento', 'accounting-entries', 'fas fa-balance-scale');
        $this->views[$viewName]->addOrderBy(['fecha', 'numero', 'idpartida'], 'date', 2);
        $this->views[$viewName]->addSearchFields(['partidas.concepto']);

        // filtros
        $this->views[$viewName]->addFilterPeriod('date', 'date', 'fecha');

        $iva = $this->codeModel->all('partidas', 'iva', 'iva');
        $this->views[$viewName]->addFilterSelect('iva', 'vat', 'iva', $iva);
        $this->views[$viewName]->addFilterCheckbox('no-iva', 'without-taxation', 'iva', 'IS', null);

        $this->views[$viewName]->addFilterNumber('debit-major', 'debit', 'debe');
        $this->views[$viewName]->addFilterNumber('debit-minor', 'debit', 'debe', '<=');
        $this->views[$viewName]->addFilterNumber('credit-major', 'credit', 'haber');
        $this->views[$viewName]->addFilterNumber('credit-minor', 'credit', 'haber', '<=');

        // disable column
        $this->views[$viewName]->disableColumn('subaccount');

        // botones
        $this->setSettings($viewName, 'btnDelete', false);
        $this->addButton($viewName, [
            'action' => 'dot-accounting-on',
            'color' => 'info',
            'icon' => 'fas fa-check-double',
            'label' => 'checked'
        ]);
        $this->addButton($viewName, [
            'action' => 'dot-accounting-off',
            'color' => 'warning',
            'icon' => 'far fa-square',
            'label' => 'unchecked'
        ]);
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
                if (false === $this->permissions->allowExport) {
                    $this->toolBox()->i18nLog()->warning('no-print-permission');
                    return true;
                }

                $code = (int)$this->request->query->get('code');
                if (!empty($code)) {
                    $this->setTemplate(false);
                    $this->ledgerReport($code);
                }
                return true;

            case 'dot-accounting-off':
                return $this->dotAccountingAction(false);

            case 'dot-accounting-on':
                return $this->dotAccountingAction(true);
        }

        return parent::execPreviousAction($action);
    }

    protected function ledgerReport(int $idSubAccount)
    {
        $subAccount = new Subcuenta();
        $subAccount->loadFromCode($idSubAccount);
        $request = $this->request->request->all();

        $ledger = new Ledger();
        $pages = $ledger->generate($subAccount->getExercise()->idempresa, $request['dateFrom'], $request['dateTo'], [
            'channel' => $request['channel'],
            'format' => $request['format'],
            'grouped' => $request['groupingtype'] ?? false,
            'subaccount-from' => $subAccount->codsubcuenta
        ]);
        $title = self::toolBox()::i18n()->trans('ledger') . ' ' . $subAccount->codsubcuenta;
        $this->exportManager->newDoc($request['format'], $title);

        // añadimos la tabla de cabecera con la info del informe
        if ($request['format'] === 'PDF') {
            $titles = [[
                self::toolBox()::i18n()->trans('subaccount') => $subAccount->codsubcuenta,
                self::toolBox()::i18n()->trans('exercise') => $subAccount->codejercicio,
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

        switch ($viewName) {
            case 'ListPartidaAsiento':
                $idsubcuenta = $this->getViewModelValue($mainViewName, 'idsubcuenta');
                $where = [new DataBaseWhere('idsubcuenta', $idsubcuenta)];
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
                    $this->prepareSubcuenta($view);
                }
                break;
        }
    }

    protected function prepareSubcuenta(BaseView $view)
    {
        $cuenta = new Cuenta();
        $idcuenta = $this->request->query->get('idcuenta', '');
        if (!empty($idcuenta) && $cuenta->loadFromCode($idcuenta)) {
            $view->model->codcuenta = $cuenta->codcuenta;
            $view->model->codejercicio = $cuenta->codejercicio;
            $view->model->idcuenta = $cuenta->idcuenta;
        }
    }

    /**
     * Set dotted status to indicated value.
     *
     * @param bool $value
     *
     * @return bool
     */
    private function dotAccountingAction(bool $value): bool
    {
        $ids = $this->request->request->get('code', []);
        if (empty($ids)) {
            $this->toolBox()->i18nLog()->warning('no-selected-item');
            return false;
        }

        $where = [new DataBaseWhere('idpartida', implode(',', $ids), 'IN')];
        $partida = new Partida();
        foreach ($partida->all($where) as $row) {
            $row->setDottedStatus($value);
        }

        $this->toolBox()->i18nLog()->notice('record-updated-correctly');
        return true;
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
