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

use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\CuentaBanco;
use FacturaScripts\Dinamic\Model\Ejercicio;

/**
 * Controller to edit a single item from the CuentaBanco model
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class EditCuentaBanco extends EditController
{
    public function getModelClassName(): string
    {
        return 'CuentaBanco';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'bank-account';
        $data['icon'] = 'fa-solid fa-piggy-bank';
        return $data;
    }

    protected function createSubAccountingView($viewName = 'ListSubcuenta'): void
    {
        $this->addListView($viewName, 'Subcuenta', 'subaccounts', 'fa-solid fa-book')
            ->addSearchFields(['codsubcuenta', 'descripcion', 'codejercicio'])
            ->addOrderBy(['codejercicio'], 'exercise', 2)
            ->setSettings('btnNew', false);
    }

    /**
     * Create tabs or views.
     */
    protected function createViews(): void
    {
        parent::createViews();

        $this->setTabsPosition('bottom');

        // disable company column if there is only one company
        if ($this->empresa->count() < 2) {
            $this->views[$this->getMainViewName()]->disableColumn('company');
        }

        $this->createSubAccountingView();
    }

    /**
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        if ($action === 'generate-subaccount') {
            $this->generateSubaccountAction();
            return true;
        }

        return parent::execPreviousAction($action);
    }

    protected function generateSubaccountAction(): void
    {
        // comprobamos permisos y token
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-update');
            return;
        } elseif (false === $this->validateFormToken()) {
            return;
        }

        $cuentaBanco = new CuentaBanco();
        $code = $this->request->query('code', '');
        if (empty($code) || false === $cuentaBanco->load($code)) {
            Tools::log()->warning('record-not-found');
            return;
        }

        if (!empty($cuentaBanco->codsubcuenta)) {
            return;
        }

        // buscamos un ejercicio abierto de la empresa
        $ejercicio = new Ejercicio();
        $where = [
            Where::eq('idempresa', $cuentaBanco->idempresa),
            Where::eq('estado', Ejercicio::EXERCISE_STATUS_OPEN),
        ];
        if (false === $ejercicio->loadWhere($where, ['fechainicio' => 'DESC'])) {
            Tools::log()->warning('exercise-not-found');
            return;
        }

        // creamos la subcuenta y la asignamos a la cuenta bancaria
        $subcuenta = $cuentaBanco->createSubcuenta($ejercicio->codejercicio);
        if (empty($subcuenta->codsubcuenta)) {
            Tools::log()->warning('record-save-error');
            return;
        }

        Tools::log()->notice('record-updated-correctly');
    }

    /**
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListSubcuenta':
                $codejercicios = $this->getExerciseOfCompany();
                $codsubcuenta = $this->getViewModelValue($this->getMainViewName(), 'codsubcuenta');
                $where = [
                    Where::in('codejercicio', $codejercicios),
                    Where::eq('codsubcuenta', $codsubcuenta),
                ];
                $codsubcuenta2 = $this->getViewModelValue($this->getMainViewName(), 'codsubcuentagasto');
                if ($codsubcuenta2 && $codsubcuenta2 != $codsubcuenta) {
                    $where[] = Where::orEq('codsubcuenta', $codsubcuenta2);
                }
                $view->loadData('', $where, ['codejercicio' => 'DESC']);

                // ocultamos la columna saldo de los totales
                unset($view->totalAmounts['saldo']);

                // si la cuenta bancaria no tiene subcuenta asignada, mostramos botón generar
                if (empty($codsubcuenta)) {
                    $this->tab($viewName)->addButton([
                        'action' => 'generate-subaccount',
                        'color' => 'success',
                        'icon' => 'fa-solid fa-magic',
                        'label' => 'generate',
                    ]);
                }
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    /**
     * Returns the list of exercises of the selected company.
     *
     * @return array
     */
    private function getExerciseOfCompany(): array
    {
        $result = [];
        $where = [
            Where::eq('idempresa', $this->getViewModelValue($this->getMainViewName(), 'idempresa'))
        ];
        foreach ($this->codeModel->all('ejercicios', 'codejercicio', 'codejercicio', false, $where) as $row) {
            $result[] = $row->code;
        }
        return $result;
    }
}
