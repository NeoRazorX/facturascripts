<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\DataSrc\Ejercicios;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\ExtendedController\ListController;
use FacturaScripts\Dinamic\Lib\Import\CSVImport;
use FacturaScripts\Dinamic\Model\CuentaEspecial;
use FacturaScripts\Dinamic\Model\WorkEvent;

/**
 * Controlador para listar los elementos del modelo Cuenta
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ListCuenta extends ListController
{
    /** @var bool Evita mostrar el aviso de saldos pendientes más de una vez por petición. */
    private $balanceJobsChecked = false;

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'accounting-accounts';
        $data['icon'] = 'fa-solid fa-book';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewsSubaccounts();
        $this->createViewsAccounts();
        $this->createViewsSpecialAccounts();
    }

    protected function createViewsAccounts(string $viewName = 'ListCuenta'): void
    {
        $this->addView($viewName, 'Cuenta', 'accounts', 'fa-solid fa-book')
            ->addSearchFields(['descripcion', 'codcuenta', 'codejercicio', 'codcuentaesp'])
            ->addOrderBy(['codejercicio desc, codcuenta'], 'code')
            ->addOrderBy(['codejercicio desc, descripcion'], 'description');

        // filters
        $this->listView($viewName)
            ->addFilterNumber('debit-major', 'debit', 'debe')
            ->addFilterNumber('debit-minor', 'debit', 'debe', '<=')
            ->addFilterNumber('credit-major', 'credit', 'haber')
            ->addFilterNumber('credit-minor', 'credit', 'haber', '<=')
            ->addFilterNumber('balance-major', 'balance', 'saldo')
            ->addFilterNumber('balance-minor', 'balance', 'saldo', '<=')
            ->addFilterSelect('codejercicio', 'exercise', 'codejercicio', Ejercicios::codeModel());

        $specialAccounts = $this->codeModel->all('cuentasesp', 'codcuentaesp', 'codcuentaesp');
        $this->addFilterSelect($viewName, 'codcuentaesp', 'special-account', 'codcuentaesp', $specialAccounts);
    }

    protected function createViewsSpecialAccounts(string $viewName = 'ListCuentaEspecial'): void
    {
        $this->addView($viewName, 'CuentaEspecial', 'special-accounts', 'fa-solid fa-newspaper')
            ->addSearchFields(['descripcion', 'codcuentaesp'])
            ->addOrderBy(['codcuentaesp'], 'code', 1)
            ->addOrderBy(['descripcion'], 'description');

        // disable buttons
        $this->tab($viewName)
            ->setSettings('btnDelete', false)
            ->setSettings('btnNew', false)
            ->setSettings('checkBoxes', false);

        // add restore button
        if ($this->user->admin) {
            $this->tab($viewName)->addButton([
                'action' => 'restore-special',
                'color' => 'warning',
                'confirm' => true,
                'icon' => 'fa-solid fa-trash-restore',
                'label' => 'restore'
            ]);
        }
    }

    protected function createViewsSubaccounts(string $viewName = 'ListSubcuenta'): void
    {
        $this->addView($viewName, 'Subcuenta', 'subaccounts', 'fa-solid fa-th-list')
            ->addSearchFields(['codsubcuenta', 'descripcion', 'codejercicio', 'codcuentaesp'])
            ->addOrderBy(['codejercicio desc, codsubcuenta'], 'code')
            ->addOrderBy(['codejercicio desc, descripcion'], 'description')
            ->addOrderBy(['debe'], 'debit')
            ->addOrderBy(['haber'], 'credit')
            ->addOrderBy(['saldo'], 'balance');

        // filters
        $this->listView($viewName)
            ->addFilterNumber('debit-major', 'debit', 'debe')
            ->addFilterNumber('debit-minor', 'debit', 'debe', '<=')
            ->addFilterNumber('credit-major', 'credit', 'haber')
            ->addFilterNumber('credit-minor', 'credit', 'haber', '<=')
            ->addFilterNumber('balance-major', 'balance', 'saldo')
            ->addFilterNumber('balance-minor', 'balance', 'saldo', '<=')
            ->addFilterSelect('codejercicio', 'exercise', 'codejercicio', Ejercicios::codeModel());

        $specialAccounts = $this->codeModel->all('cuentasesp', 'codcuentaesp', 'codcuentaesp');
        $this->addFilterSelect($viewName, 'codcuentaesp', 'special-account', 'codcuentaesp', $specialAccounts);
    }

    /**
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        if ($action === 'restore-special') {
            $this->restoreSpecialAccountsAction();
        }

        return parent::execPreviousAction($action);
    }

    protected function loadData($viewName, $view)
    {
        parent::loadData($viewName, $view);

        // si la vista tiene una columna saldo en los totales, la eliminamos
        if (isset($view->totalAmounts['saldo'])) {
            unset($view->totalAmounts['saldo']);
        }

        $this->checkPendingBalanceJobs();
    }

    /**
     * Informa al usuario si todavía hay eventos en la cola de trabajo
     * recalculando los saldos de cuentas y subcuentas.
     */
    private function checkPendingBalanceJobs(): void
    {
        if ($this->balanceJobsChecked) {
            return;
        }
        $this->balanceJobsChecked = true;

        $where = [
            Where::eq('done', false),
            Where::in('name', [
                'Model.Partida.Save',
                'Model.Partida.Delete',
                'Model.Subcuenta.Update',
                'Model.Subcuenta.Delete',
                'Model.Cuenta.Update',
                'Model.Cuenta.Delete',
            ]),
        ];

        $pending = WorkEvent::count($where);
        if ($pending > 0) {
            Tools::log()->warning('balances-updating-in-background', ['%count%' => $pending]);
        }
    }

    protected function restoreSpecialAccountsAction(): void
    {
        if (false === $this->user->admin) {
            Tools::log()->warning('not-allowed-modify');
            return;
        } elseif (false === $this->request->isMethod(Request::METHOD_POST)) {
            Tools::log()->warning('invalid-request');
            return;
        } elseif (false === $this->validateFormToken()) {
            return;
        }

        $sql = CSVImport::updateTableSQL(CuentaEspecial::tableName());
        if (!empty($sql)) {
            $this->dataBase->exec($sql);
        }

        Tools::log()->notice('record-updated-correctly');
    }
}
