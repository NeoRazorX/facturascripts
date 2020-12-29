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
use FacturaScripts\Core\Lib\Accounting\ClosingToAcounting;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Dinamic\Lib\Accounting\AccountingPlanExport;
use FacturaScripts\Dinamic\Lib\Accounting\AccountingPlanImport;
use FacturaScripts\Dinamic\Model\Ejercicio;

/**
 * Controller to edit a single item from the Ejercicio model
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Artex Trading sa         <jcuello@artextrading.com>
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 * @author Oscar G. Villa González  <ogvilla@gmail.com>
 */
class EditEjercicio extends EditController
{

    /**
     *
     * @return string
     */
    public function getModelClassName()
    {
        return 'Ejercicio';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'exercise';
        $data['icon'] = 'fas fa-calendar-alt';
        return $data;
    }

    /**
     * Add action buttons.
     */
    protected function addButtonActions()
    {
        $status = $this->getViewModelValue('EditEjercicio', 'estado');
        switch ($status) {
            case Ejercicio::EXERCISE_STATUS_OPEN:
                $this->addButton('EditEjercicio', [
                    'row' => 'footer-actions',
                    'action' => 'import-accounting',
                    'color' => 'warning',
                    'icon' => 'fas fa-file-import',
                    'label' => 'import-accounting-plan',
                    'type' => 'modal'
                ]);

                $this->addButton('EditEjercicio', [
                    'row' => 'footer-actions',
                    'action' => 'close-exercise',
                    'color' => 'danger',
                    'icon' => 'fas fa-calendar-check',
                    'label' => 'close-exercise',
                    'type' => 'modal'
                ]);

                $model = $this->views['EditEjercicio']->model;
                $model->copysubaccounts = true;
                break;

            case Ejercicio::EXERCISE_STATUS_CLOSED:
                $this->addButton('EditEjercicio', [
                    'row' => 'footer-actions',
                    'action' => 'open-exercise',
                    'color' => 'warning',
                    'icon' => 'fas fa-calendar-plus',
                    'label' => 'open-exercise',
                    'type' => 'modal'
                ]);
                break;
        }
    }

    /**
     * Check that user allowed modify and load exercise data
     *
     * @param string $code
     *
     * @return bool
     */
    private function checkAndLoad($code): bool
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return false;
        }

        if (false === $this->getModel()->loadFromCode($code)) {
            $this->toolBox()->i18nLog()->error('record-not-found');
            return false;
        }

        return true;
    }

    /**
     *
     * @return bool
     */
    protected function closeExercise(): bool
    {
        $code = $this->request->request->get('codejercicio');
        if (false === $this->checkAndLoad($code)) {
            return false;
        }

        $data = [
            'journalClosing' => $this->request->request->get('iddiario-closing'),
            'journalOpening' => $this->request->request->get('iddiario-opening'),
            'copySubAccounts' => (bool) $this->request->request->get('copysubaccounts', false)
        ];

        $model = $this->getModel();
        $closing = new ClosingToAcounting();
        if ($closing->exec($model, $data)) {
            $this->toolBox()->i18nLog()->notice('closing-accounting-completed');
            return true;
        }

        return false;
    }

    /**
     * Load views.
     */
    protected function createViews()
    {
        parent::createViews();

        /// disable company column if there is only one company
        if ($this->empresa->count() < 2) {
            $this->views[$this->getMainViewName()]->disableColumn('company');
        }

        $this->createViewsAccounting();
        $this->createViewsSubaccounting();
        $this->createViewsAccountingEntries();
    }

    /**
     *
     * @param string $viewName
     */
    protected function createViewsAccounting(string $viewName = 'ListCuenta')
    {
        $this->addListView($viewName, 'Cuenta', 'accounts', 'fas fa-book');
        $this->views[$viewName]->addOrderBy(['codcuenta'], 'code', 1);
        $this->views[$viewName]->searchFields[] = 'codcuenta';
        $this->views[$viewName]->searchFields[] = 'descripcion';

        /// disable columns
        $this->views[$viewName]->disableColumn('fiscal-exercise');
        $this->views[$viewName]->disableColumn('parent-account');
    }

    /**
     *
     * @param string $viewName
     */
    protected function createViewsAccountingEntries(string $viewName = 'ListAsiento')
    {
        $this->addListView($viewName, 'Asiento', 'special-accounting-entries', 'fas fa-balance-scale');
        $this->views[$viewName]->addOrderBy(['fecha', 'numero'], 'date');
        $this->views[$viewName]->searchFields[] = 'numero';
        $this->views[$viewName]->searchFields[] = 'concepto';

        /// disable columns
        $this->views[$viewName]->disableColumn('exercise');

        /// disable button
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     *
     * @param string $viewName
     */
    protected function createViewsSubaccounting(string $viewName = 'ListSubcuenta')
    {
        $this->addListView($viewName, 'Subcuenta', 'subaccounts');
        $this->views[$viewName]->addOrderBy(['codsubcuenta'], 'code', 1);
        $this->views[$viewName]->addOrderBy(['saldo'], 'balance');
        $this->views[$viewName]->searchFields[] = 'codsubcuenta';
        $this->views[$viewName]->searchFields[] = 'descripcion';

        /// disable columns
        $this->views[$viewName]->disableColumn('fiscal-exercise');
    }

    /**
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'close-exercise':
                $this->closeExercise();
                return true;

            case 'export-accounting':
                return $this->exportAccountingPlan();

            case 'import-accounting':
                return $this->importAccountingPlan();

            case 'open-exercise':
                $this->openExercise();
                return true;

            default:
                return parent::execPreviousAction($action);
        }
    }

    /**
     * Export AccountingPlan to CSV file.
     *
     * @return bool
     */
    protected function exportAccountingPlan()
    {
        $codejercicio = $this->request->get('code', '');
        if (empty($codejercicio)) {
            $this->toolBox()->i18nLog()->error('exercise-not-found');
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
    protected function importAccountingPlan()
    {
        $codejercicio = $this->request->request->get('codejercicio', '');
        if (empty($codejercicio)) {
            $this->toolBox()->i18nLog()->error('exercise-not-found');
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
                    $this->toolBox()->i18nLog()->notice('record-updated-correctly');
                    return true;
                }

                $this->toolBox()->i18nLog()->error('record-save-error');
                break;

            case 'text/csv':
            case 'text/plain':
                if ($accountingPlanImport->importCSV($uploadFile->getPathname(), $codejercicio)) {
                    $this->toolBox()->i18nLog()->notice('record-updated-correctly');
                    return true;
                }

                $this->toolBox()->i18nLog()->error('record-save-error');
                break;

            default:
                $this->toolBox()->i18nLog()->error('file-not-supported');
        }

        return true;
    }

    /**
     *
     * @param string $codejercicio
     *
     * @return bool
     */
    protected function importDefaultPlan(string $codejercicio)
    {
        $codpais = $this->toolBox()->appSettings()->get('default', 'codpais');
        $filePath = \FS_FOLDER . '/Dinamic/Data/Codpais/' . $codpais . '/defaultPlan.csv';
        if (false === \file_exists($filePath)) {
            $this->toolBox()->i18nLog()->warning('file-not-found', ['%fileName%' => $filePath]);
            return true;
        }

        $accountingPlanImport = new AccountingPlanImport();
        if ($accountingPlanImport->importCSV($filePath, $codejercicio)) {
            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            return true;
        }

        $this->toolBox()->i18nLog()->error('record-save-error');
        return true;
    }

    /**
     * Load view data procedure
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $codejercicio = $this->getViewModelValue('EditEjercicio', 'codejercicio');

        switch ($viewName) {
            case 'EditEjercicio':
                parent::loadData($viewName, $view);
                $this->addButtonActions();
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
                break;
        }
    }

    /**
     * Re-open closed exercise
     */
    protected function openExercise(): bool
    {
        $code = $this->request->request->get('codejercicio');
        if (false === $this->checkAndLoad($code)) {
            return false;
        }

        $data = [
            'deleteClosing' => $this->request->request->get('delete-closing', true),
            'deleteOpening' => $this->request->request->get('delete-opening', false)
        ];
        $model = $this->getModel();

        $closing = new ClosingToAcounting();
        if ($closing->delete($model, $data)) {
            $this->toolBox()->i18nLog()->notice('opening-acounting-completed');
            return true;
        }

        return false;
    }
}
