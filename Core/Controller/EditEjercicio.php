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
                $this->exportAccountingPlan();
                return true;

            case 'import-accounting':
                $this->importAccountingPlan();
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
        $code = $this->request->get('code', '');
        if (empty($code)) {
            $this->miniLog->alert($this->i18n->trans('exercise-not-found'));
            return false;
        }

        $this->setTemplate(false);
        $this->response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $this->response->headers->set('Content-Disposition', 'attachment;filename=' . $code . '.csv');
        $accountingPlanExport = new AccountingPlanExport();
        $this->response->setContent($accountingPlanExport->exportCSV($code));
        return true;
    }

    /**
     * Import AccountingPlan from any supported file type.
     *
     * @return bool
     */
    protected function importAccountingPlan()
    {
        $code = $this->request->request->get('codejercicio', '');
        if (empty($code)) {
            $this->miniLog->alert($this->i18n->trans('exercise-not-found'));
            return false;
        }

        $uploadFile = $this->request->files->get('accountingfile', false);
        if ($uploadFile === false) {
            $this->miniLog->alert($this->i18n->trans('file-not-found', ['%fileName%' => '']));
            return false;
        }

        $accountingPlanImport = new AccountingPlanImport();
        switch ($uploadFile->getMimeType()) {
            case 'application/xml':
            case 'text/xml':
                if ($accountingPlanImport->importXML($uploadFile->getPathname(), $code)) {
                    $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));
                } else {
                    $this->miniLog->error($this->i18n->trans('record-save-error'));
                }
                break;

            case 'text/csv':
            case 'text/plain':
                if ($accountingPlanImport->importCSV($uploadFile->getPathname(), $code)) {
                    $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));
                } else {
                    $this->miniLog->error($this->i18n->trans('record-save-error'));
                }
                break;

            default:
                $this->miniLog->error($this->i18n->trans('file-not-supported'));
        }

        return true;
    }
}
