<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\ExtendedController\EditReportAccounting;
use FacturaScripts\Core\Model\Subcuenta;
use FacturaScripts\Dinamic\Lib\Accounting\BalanceSheet;
use FacturaScripts\Dinamic\Lib\Accounting\IncomeAndExpenditure;
use FacturaScripts\Dinamic\Lib\Accounting\ProfitAndLoss;
use FacturaScripts\Dinamic\Model\Balance;
use FacturaScripts\Dinamic\Model\BalanceCuenta;
use FacturaScripts\Dinamic\Model\BalanceCuentaA;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\ReportBalance;

/**
 * Description of EditReportBalance
 *
 * @author Carlos Garcia Gomez  <carlos@facturascripts.com>
 * @author Jose Antonio Cuello  <yopli2000@gmail.com>
 */
class EditReportBalance extends EditReportAccounting
{
    public function getModelClassName(): string
    {
        return 'ReportBalance';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'reports';
        $data['title'] = 'balances';
        $data['icon'] = 'fas fa-book';
        return $data;
    }

    protected function createViews()
    {
        parent::createViews();
        // disable company column if there is only one company
        if ($this->empresa->count() < 2) {
            $this->views[$this->getMainViewName()]->disableColumn('company');
        }

        $this->createViewsBalances();
        $this->setTabsPosition('bottom');
    }

    protected function createViewsBalances(string $viewName = 'ListBalance')
    {
        $this->addListView($viewName, 'Balance', 'preferences');
        $this->views[$viewName]->addOrderBy(['codbalance'], 'code');
        $this->views[$viewName]->addOrderBy(['descripcion1'], 'description-1');
        $this->views[$viewName]->addOrderBy(['descripcion2'], 'description-2');
        $this->views[$viewName]->addOrderBy(['descripcion3'], 'description-3');
        $this->views[$viewName]->addOrderBy(['descripcion4'], 'description-4');
        $this->views[$viewName]->addOrderBy(['descripcion4ba'], 'description-4ba');
        $this->views[$viewName]->addSearchFields([
            'codbalance', 'naturaleza', 'descripcion1', 'descripcion2',
            'descripcion3', 'descripcion4', 'descripcion4ba'
        ]);

        // disable column
        $this->views[$viewName]->disableColumn('nature');

        // disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'checkBoxes', false);
    }

    protected function execAfterAction($action)
    {
        if ($action === 'find-problems') {
            $this->findBadAccounts();
            $this->findMissingAccounts();
        }

        parent::execAfterAction($action);
    }

    protected function findBadAccounts()
    {
        // cargamos el ejercicio para la fecha de inicio
        $exercise = new Ejercicio();
        $exercise->idempresa = $this->getModel()->idcompany;
        if (false === $exercise->loadFromDate($this->getModel()->startdate, false, false)) {
            self::toolBox()->i18nLog()->warning('exercise-not-found');
            return;
        }

        // recorremos todas las cuentas
        $accountModel = new Cuenta();
        foreach ($accountModel->all([], [], 0, 0) as $account) {
            if (empty($account->parent_codcuenta)) {
                continue;
            }

            // comprobamos que el campo parent_codcuenta son los primeros caracteres del campo codcuenta
            $len = strlen($account->parent_codcuenta);
            if (substr($account->codcuenta, 0, $len) !== $account->parent_codcuenta) {
                $this->toolBox()->i18nLog()->warning('account-bad-parent', ['%codcuenta%' => $account->codcuenta]);
            }
        }

        // recorremos todas las subcuentas del ejercicio
        $subaccountModel = new Subcuenta();
        $where = [new DataBaseWhere('codejercicio', $exercise->codejercicio)];
        foreach ($subaccountModel->all($where, [], 0, 0) as $subaccount) {
            // comprobamos que el campo codcuenta son los primeros caracteres del campo codsubcuenta
            $len = strlen($subaccount->codcuenta);
            if (substr($subaccount->codsubcuenta, 0, $len) !== $subaccount->codcuenta) {
                $this->toolBox()->i18nLog()->warning('subaccount-bad-codcuenta', ['%codsubcuenta%' => $subaccount->codsubcuenta]);
            }
        }
    }

    protected function findMissingAccounts()
    {
        // cargamos el ejercicio para la fecha de inicio
        $exercise = new Ejercicio();
        $exercise->idempresa = $this->getModel()->idcompany;
        if (false === $exercise->loadFromDate($this->getModel()->startdate, false, false)) {
            self::toolBox()->i18nLog()->warning('exercise-not-found');
            return;
        }

        // buscamos las cuentas con saldo
        $cuentaModel = new Cuenta();
        $whereCuenta = [new DataBaseWhere('codejercicio', $exercise->codejercicio)];
        foreach ($cuentaModel->all($whereCuenta, [], 0, 0) as $cuenta) {
            // excluimos las cuentas que empiezan por 6 o 7
            if (strpos($cuenta->codcuenta, '6') === 0 || strpos($cuenta->codcuenta, '7') === 0) {
                continue;
            }

            // calculamos el saldo
            $saldo = 0.0;
            foreach ($cuenta->getSubcuentas() as $subcuenta) {
                $saldo += $subcuenta->saldo;
            }
            // si el saldo es cero, no hacemos nada
            if (empty($saldo)) {
                continue;
            }

            // si el balance es de tipo abreviado, buscamos la relación en BalanceCuentaA
            $balanceCuenta = $this->getModel()->subtype === ReportBalance::SUBTYPE_ABBREVIATED ?
                new BalanceCuentaA() : new BalanceCuenta();
            $whereBalance = [
                new DataBaseWhere('codbalance', implode(',', $this->getBalanceCodes()), 'IN'),
                new DataBaseWhere('codcuenta', $cuenta->codcuenta)
            ];
            if ($balanceCuenta->loadFromCode('', $whereBalance)) {
                continue;
            }

            // comprobamos el padre
            if ($cuenta->parent_codcuenta) {
                $wherePadre = [
                    new DataBaseWhere('codbalance', implode(',', $this->getBalanceCodes()), 'IN'),
                    new DataBaseWhere('codcuenta', $cuenta->parent_codcuenta)
                ];
                if ($balanceCuenta->loadFromCode('', $wherePadre)) {
                    continue;
                }
            }
            if (strlen($cuenta->codcuenta) > 1) {
                $wherePadre = [
                    new DataBaseWhere('codbalance', implode(',', $this->getBalanceCodes()), 'IN'),
                    new DataBaseWhere('codcuenta', substr($cuenta->codcuenta, 0, -1))
                ];
                if ($balanceCuenta->loadFromCode('', $wherePadre)) {
                    continue;
                }
            }

            // si no existe la relación, avisamos
            $this->toolBox()->i18nLog()->info('account-missing-in-balance', ['%codcuenta%' => $cuenta->codcuenta]);
        }
    }

    /**
     * Generate Balance Amounts data for report
     *
     * @param ReportBalance $model
     * @param string $format
     *
     * @return array
     */
    protected function generateReport($model, $format): array
    {
        $params = [
            'channel' => $model->channel,
            'format' => $format,
            'idcompany' => $model->idcompany,
            'subtype' => $model->subtype,
            'comparative' => $model->comparative
        ];

        switch ($model->type) {
            case ReportBalance::TYPE_SHEET:
                $balanceAmount = new BalanceSheet();
                $balanceAmount->setExerciseFromDate($model->idcompany, $model->startdate);
                return $balanceAmount->generate($model->startdate, $model->enddate, $params);

            case ReportBalance::TYPE_PROFIT:
                $profitAndLoss = new ProfitAndLoss();
                $profitAndLoss->setExerciseFromDate($model->idcompany, $model->startdate);
                return $profitAndLoss->generate($model->startdate, $model->enddate, $params);

            case ReportBalance::TYPE_INCOME:
                $incomeAndExpenditure = new IncomeAndExpenditure();
                $incomeAndExpenditure->setExerciseFromDate($model->idcompany, $model->startdate);
                return $incomeAndExpenditure->generate($model->startdate, $model->enddate, $params);
        }

        return [];
    }

    protected function getBalanceCodes(): array
    {
        $codes = [];

        $balanceModel = new Balance();
        $where = $this->getPreferencesWhere();
        foreach ($balanceModel->all($where, ['codbalance' => 'ASC'], 0, 0) as $balance) {
            $codes[] = $balance->codbalance;
        }

        return $codes;
    }

    protected function getPreferencesWhere(): array
    {
        switch ($this->getModel()->type) {
            case 'balance-sheet':
                return [
                    new DataBaseWhere('naturaleza', 'A'),
                    new DataBaseWhere('naturaleza', 'P', '=', 'OR')
                ];

            case 'profit-and-loss':
                return [new DataBaseWhere('naturaleza', 'PG')];

            case 'income-and-expenses':
                return [new DataBaseWhere('naturaleza', 'IG')];
        }

        return [];
    }

    /**
     * Loads the data to display.
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->getMainViewName();
        switch ($viewName) {
            case 'ListBalance':
                $where = $this->getPreferencesWhere();
                $view->loadData('', $where);
                break;

            case $mainViewName:
                parent::loadData($viewName, $view);
                $this->loadWidgetValues($view);
                if (false === $view->model->exists()) {
                    break;
                }
                // añadimos el botón para encontrar problemas
                $this->addButton($viewName, [
                    'action' => 'find-problems',
                    'color' => 'warning',
                    'icon' => 'fas fa-search',
                    'label' => 'find-problems'
                ]);
                break;
        }
    }

    /**
     * Load values into special widget columns
     *
     * @param BaseView $view
     */
    protected function loadWidgetValues(BaseView $view)
    {
        $columnType = $view->columnForField('type');
        if ($columnType && $columnType->widget->getType() === 'select') {
            $columnType->widget->setValuesFromArray(ReportBalance::typeList());
        }

        $columnFormat = $view->columnForField('subtype');
        if ($columnFormat && $columnFormat->widget->getType() === 'select') {
            $columnFormat->widget->setValuesFromArray(ReportBalance::subtypeList());
        }
    }
}
