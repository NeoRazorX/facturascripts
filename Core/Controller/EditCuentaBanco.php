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
            ->addOrderBy(['codejercicio'], 'exercise', 2)
            ->setSettings('btnNew', false);
    }

    /**
     * Create tabs or views.
     */
    protected function createViews()
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
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListSubcuenta':
                $codejercicios = implode(',', $this->getExerciseOfCompany());
                $codsubcuenta = $this->getViewModelValue($this->getMainViewName(), 'codsubcuenta');
                $where = [
                    new DataBaseWhere('codejercicio', $codejercicios, 'IN'),
                    new DataBaseWhere('codsubcuenta', $codsubcuenta),
                ];
                $codsubcuenta2 = $this->getViewModelValue($this->getMainViewName(), 'codsubcuentagasto');
                if ($codsubcuenta2 && $codsubcuenta2 != $codsubcuenta) {
                    $where[] = new DataBaseWhere('codsubcuenta', $codsubcuenta2, '=', 'OR');
                }
                $view->loadData('', $where, ['codejercicio' => 'DESC']);

                // ocultamos la columna saldo de los totales
                unset($view->totalAmounts['saldo']);
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
            new DataBaseWhere('idempresa', $this->getViewModelValue($this->getMainViewName(), 'idempresa'))
        ];
        foreach ($this->codeModel->all('ejercicios', 'codejercicio', 'codejercicio', false, $where) as $row) {
            $result[] = $row->code;
        }
        return $result;
    }
}
