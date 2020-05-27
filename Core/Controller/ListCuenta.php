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

use FacturaScripts\Dinamic\Lib\ExtendedController\ListController;
use FacturaScripts\Dinamic\Lib\Import\CSVImport;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\CuentaEspecial;

/**
 * Controller to list the items in the Cuenta model.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class ListCuenta extends ListController
{

    /**
     *
     * @var CodeModel[]
     */
    protected $exerciseValues;

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'accounting-accounts';
        $data['icon'] = 'fas fa-book';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        /// load exercises in class to use in filters
        $this->exerciseValues = $this->codeModel->all('ejercicios', 'codejercicio', 'nombre');

        $this->createViewsSubaccounts();
        $this->createViewsAccounts();
        $this->createViewsSpecialAcounts();
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsAccounts(string $viewName = 'ListCuenta')
    {
        $this->addView($viewName, 'Cuenta', 'accounts', 'fas fa-book');
        $this->addOrderBy($viewName, ['codejercicio desc, codcuenta'], 'code');
        $this->addOrderBy($viewName, ['codejercicio desc, descripcion'], 'description');
        $this->addSearchFields($viewName, ['descripcion', 'codcuenta', 'codejercicio', 'codcuentaesp']);

        /// filters
        $this->addFilterSelect($viewName, 'codejercicio', 'exercise', 'codejercicio', $this->exerciseValues);

        $specialAccounts = $this->codeModel->all('cuentasesp', 'codcuentaesp', 'codcuentaesp');
        $this->addFilterSelect($viewName, 'codcuentaesp', 'special-account', 'codcuentaesp', $specialAccounts);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsSpecialAcounts(string $viewName = 'ListCuentaEspecial')
    {
        $this->addView($viewName, 'CuentaEspecial', 'special-accounts', 'fas fa-newspaper');
        $this->addOrderBy($viewName, ['codcuentaesp'], 'code', 1);
        $this->addOrderBy($viewName, ['descripcion'], 'description');
        $this->addSearchFields($viewName, ['descripcion', 'codcuentaesp']);

        /// disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'checkBoxes', false);

        /// add restore button
        if ($this->user->admin) {
            $this->addButton($viewName, [
                'action' => 'restore-special',
                'color' => 'warning',
                'confirm' => true,
                'icon' => 'fas fa-trash-restore',
                'label' => 'restore'
            ]);
        }
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsSubaccounts(string $viewName = 'ListSubcuenta')
    {
        $this->addView($viewName, 'Subcuenta', 'subaccounts', 'fas fa-th-list');
        $this->addOrderBy($viewName, ['codejercicio desc, codsubcuenta'], 'code');
        $this->addOrderBy($viewName, ['codejercicio desc, descripcion'], 'description');
        $this->addOrderBy($viewName, ['saldo'], 'balance');
        $this->addSearchFields($viewName, ['codsubcuenta', 'descripcion', 'codejercicio', 'codcuentaesp']);

        /// filters
        $this->addFilterSelect($viewName, 'codejercicio', 'exercise', 'codejercicio', $this->exerciseValues);

        $specialAccounts = $this->codeModel->all('cuentasesp', 'codcuentaesp', 'codcuentaesp');
        $this->addFilterSelect($viewName, 'codcuentaesp', 'special-account', 'codcuentaesp', $specialAccounts);

        $this->addFilterCheckbox($viewName, 'saldo', 'balance', 'saldo', '!=', 0);

        /// disable new button
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     * 
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

    protected function restoreSpecialAccountsAction()
    {
        $sql = CSVImport::updateTableSQL(CuentaEspecial::tableName());
        if (!empty($sql)) {
            $this->dataBase->exec($sql);
        }

        $this->toolBox()->i18nLog()->notice('record-updated-correctly');
    }
}
