<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Lib\Accounting\AccountingPlanExport;
use FacturaScripts\Dinamic\Lib\Accounting\AccountingPlanImport;

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
     * 
     * @param string $viewName
     */
    protected function createAccountingView($viewName = 'ListCuenta')
    {
        $this->addListView($viewName, 'Cuenta', 'accounts', 'fas fa-book');
        $this->views[$viewName]->addOrderBy(['codcuenta'], 'code', 1);
        $this->views[$viewName]->searchFields[] = 'descripcion';

        /// disable columns
        $this->views[$viewName]->disableColumn('fiscal-exercise');
        $this->views[$viewName]->disableColumn('parent-account');
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createSubAccountingView($viewName = 'ListSubcuenta')
    {
        $this->addListView($viewName, 'Subcuenta', 'subaccounts');
        $this->views[$viewName]->addOrderBy(['codsubcuenta'], 'code', 1);
        $this->views[$viewName]->addOrderBy(['saldo'], 'balance');
        $this->views[$viewName]->searchFields[] = 'descripcion';

        /// disable columns
        $this->views[$viewName]->disableColumn('fiscal-exercise');
    }

    /**
     * Load views.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->createAccountingView();
        $this->createSubAccountingView();
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
        $where = [new DataBaseWhere('codejercicio', $codejercicio)];

        switch ($viewName) {
            case 'ListCuenta':
                $view->loadData(false, $where, ['codcuenta' => 'ASC']);
                break;

            case 'ListSubcuenta':
                $view->loadData(false, $where, ['codsubcuenta' => 'ASC']);
                break;

            default:
                parent::loadData($viewName, $view);
        }
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
            case 'export-accounting':
                return $this->exportAccountingPlan();

            case 'import-accounting':
                return $this->importAccountingPlan();

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
        if (!file_exists($filePath)) {
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
}
