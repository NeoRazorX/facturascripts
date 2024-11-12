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
use FacturaScripts\Core\Lib\Accounting\ClosingToAcounting;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Accounting\AccountingPlanExport;
use FacturaScripts\Dinamic\Lib\Accounting\AccountingPlanImport;
use FacturaScripts\Dinamic\Model\Ejercicio;

/**
 * Controller to edit a single item from the Ejercicio model
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Francesc Pineda Segarra       <francesc.pineda.segarra@gmail.com>
 * @author Oscar G. Villa González       <ogvilla@gmail.com>
 */
class EditEjercicio extends EditController
{
    public function getModelClassName(): string
    {
        return 'Ejercicio';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'exercise';
        $data['icon'] = 'fa-solid fa-calendar-alt';
        return $data;
    }

    /**
     * Add action buttons.
     */
    protected function addExerciseActionButtons(string $viewName): void
    {
        $status = $this->getViewModelValue($viewName, 'estado');
        switch ($status) {
            case Ejercicio::EXERCISE_STATUS_OPEN:
                $this->addButton($viewName, [
                    'row' => 'footer-actions',
                    'action' => 'import-accounting',
                    'color' => 'warning',
                    'icon' => 'fa-solid fa-file-import',
                    'label' => 'import-accounting-plan',
                    'type' => 'modal'
                ]);

                $this->addButton($viewName, [
                    'row' => 'footer-actions',
                    'action' => 'close-exercise',
                    'color' => 'danger',
                    'icon' => 'fa-solid fa-calendar-check',
                    'label' => 'close-exercise',
                    'type' => 'modal'
                ]);
                break;

            case Ejercicio::EXERCISE_STATUS_CLOSED:
                $this->addButton($viewName, [
                    'row' => 'footer-actions',
                    'action' => 'open-exercise',
                    'color' => 'warning',
                    'icon' => 'fa-solid fa-calendar-plus',
                    'label' => 'open-exercise',
                    'type' => 'modal'
                ]);
                break;
        }
    }

    private function checkAndLoad(string $code): bool
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return false;
        }

        if (false === $this->getModel()->loadFromCode($code)) {
            Tools::log()->error('record-not-found');
            return false;
        }

        return true;
    }

    protected function closeExerciseAction(): bool
    {
        $code = $this->request->request->get('codejercicio');
        if (false === $this->checkAndLoad($code)) {
            return true;
        }

        $data = [
            'journalClosing' => $this->request->request->get('iddiario-closing'),
            'journalOpening' => $this->request->request->get('iddiario-opening'),
            'copySubAccounts' => (bool)$this->request->request->get('copysubaccounts', false)
        ];

        $model = $this->getModel();
        $closing = new ClosingToAcounting();
        if ($closing->exec($model, $data)) {
            Tools::log()->notice('closing-accounting-completed');
        }
        // error message not needed
        return true;
    }

    /**
     * Load views.
     */
    protected function createViews()
    {
        parent::createViews();

        // disable company column if there is only one company
        if ($this->empresa->count() < 2) {
            $this->views[$this->getMainViewName()]->disableColumn('company');
        }

        $this->createViewsAccounting();
        $this->createViewsSubaccounting();
        $this->createViewsAccountingEntries();
    }

    protected function createViewsAccounting(string $viewName = 'ListCuenta'): void
    {
        $this->addListView($viewName, 'Cuenta', 'accounts', 'fa-solid fa-book')
            ->addOrderBy(['codcuenta'], 'code', 1)
            ->addSearchFields(['codcuenta', 'descripcion']);

        // disable columns
        $this->views[$viewName]->disableColumn('fiscal-exercise');
        $this->views[$viewName]->disableColumn('parent-account');
    }

    protected function createViewsAccountingEntries(string $viewName = 'ListAsiento'): void
    {
        $this->addListView($viewName, 'Asiento', 'special-accounting-entries', 'fa-solid fa-balance-scale')
            ->addOrderBy(['fecha', 'numero'], 'date')
            ->addSearchFields(['concepto', 'numero']);

        // disable columns
        $this->views[$viewName]->disableColumn('exercise');

        // disable button
        $this->setSettings($viewName, 'btnNew', false);
    }

    protected function createViewsSubaccounting(string $viewName = 'ListSubcuenta'): void
    {
        $this->addListView($viewName, 'Subcuenta', 'subaccounts')
            ->addOrderBy(['codsubcuenta'], 'code', 1)
            ->addOrderBy(['saldo'], 'balance')
            ->addSearchFields(['codsubcuenta', 'descripcion']);

        // disable columns
        $this->views[$viewName]->disableColumn('fiscal-exercise');
    }

    /**
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'close-exercise':
                return $this->closeExerciseAction();

            case 'export-accounting':
                return $this->exportAccountingPlan();

            case 'import-accounting':
                return $this->importAccountingPlan();

            case 'open-exercise':
                return $this->openExerciseAction();
        }

        return parent::execPreviousAction($action);
    }

    /**
     * Export AccountingPlan to CSV file.
     *
     * @return bool
     */
    protected function exportAccountingPlan(): bool
    {
        if (false === $this->permissions->allowImport) {
            Tools::log()->warning('no-print-permission');
            return true;
        }

        $codejercicio = $this->request->get('code', '');
        if (empty($codejercicio)) {
            Tools::log()->error('exercise-not-found');
            return true;
        }

        $this->setTemplate(false);
        $this->response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $this->response->headers->set('Content-Disposition', 'attachment;filename=' . $codejercicio . '.csv');
        $accountingPlanExport = new AccountingPlanExport();
        $this->response->setContent($accountingPlanExport->exportCSV($codejercicio));
        return false;
    }

    /**
     * Import AccountingPlan from any supported file type.
     *
     * @return bool
     */
    protected function importAccountingPlan(): bool
    {
        if (false === $this->permissions->allowImport) {
            Tools::log()->warning('no-import-permission');
            return true;
        }

        $codejercicio = $this->request->request->get('codejercicio', '');
        if (empty($codejercicio)) {
            Tools::log()->error('exercise-not-found');
            return true;
        }

        $uploadFile = $this->request->files->get('accountingfile');
        if (empty($uploadFile)) {
            return $this->importDefaultPlan($codejercicio);
        }

        $accountingPlanImport = new AccountingPlanImport();
        switch ($uploadFile->getMimeType()) {
            case 'application/xml':
            case 'text/xml':
                if ($accountingPlanImport->importXML($uploadFile->getPathname(), $codejercicio)) {
                    Tools::log()->notice('record-updated-correctly');
                    return true;
                }
                Tools::log()->error('record-save-error');
                return true;

            case 'text/csv':
            case 'text/plain':
                if ($accountingPlanImport->importCSV($uploadFile->getPathname(), $codejercicio)) {
                    Tools::log()->notice('record-updated-correctly');
                    return true;
                }
                Tools::log()->error('record-save-error');
                return true;
        }

        Tools::log()->error('file-not-supported');
        return true;
    }

    protected function importDefaultPlan(string $codejercicio): bool
    {
        $filePath = FS_FOLDER . '/Dinamic/Data/Lang/' . FS_LANG . '/defaultPlan.csv';
        if (false === file_exists($filePath)) {
            $codpais = Tools::settings('default', 'codpais');
            $filePath = FS_FOLDER . '/Dinamic/Data/Codpais/' . $codpais . '/defaultPlan.csv';
        }

        if (false === file_exists($filePath)) {
            Tools::log()->warning('file-not-found', ['%fileName%' => $filePath]);
            return true;
        }

        $accountingPlanImport = new AccountingPlanImport();
        if ($accountingPlanImport->importCSV($filePath, $codejercicio)) {
            Tools::log()->notice('record-updated-correctly');
            return true;
        }

        Tools::log()->error('record-save-error');
        return true;
    }

    /**
     * Load view data procedure
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $codejercicio = $this->getViewModelValue('EditEjercicio', 'codejercicio');

        switch ($viewName) {
            case 'EditEjercicio':
                parent::loadData($viewName, $view);
                $this->addExerciseActionButtons($viewName);
                break;

            case 'ListAsiento':
                $where = [
                    new DataBaseWhere('codejercicio', $codejercicio),
                    new DataBaseWhere('operacion', null, 'IS NOT')
                ];
                $view->loadData('', $where);
                break;

            case 'ListCuenta':
            case 'ListSubcuenta':
                $where = [new DataBaseWhere('codejercicio', $codejercicio)];
                $view->loadData('', $where);

                // ocultamos la columna saldo de los totales
                unset($view->totalAmounts['saldo']);
                break;
        }
    }

    /**
     * Re-open closed exercise.
     *
     * @return bool
     */
    protected function openExerciseAction(): bool
    {
        $code = $this->request->request->get('codejercicio');
        if (false === $this->checkAndLoad($code)) {
            return true;
        }

        $data = [
            'deleteClosing' => $this->request->request->get('delete-closing', true),
            'deleteOpening' => $this->request->request->get('delete-opening', false)
        ];
        $model = $this->getModel();

        $closing = new ClosingToAcounting();
        if ($closing->delete($model, $data)) {
            Tools::log()->notice('opening-acounting-completed');
        }
        // error message not needed
        return true;
    }
}
