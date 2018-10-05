<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\Accounting\AccountingPlanImport;
use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to edit a single item from the Ejercicio model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class EditEjercicio extends ExtendedController\PanelController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'exercise';
        $pagedata['menu'] = 'accounting';
        $pagedata['icon'] = 'fas fa-calendar-alt';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Load views.
     */
    protected function createViews()
    {
        $this->addEditView('EditEjercicio', 'Ejercicio', 'exercise');
        $this->addListView('ListCuenta', 'Cuenta', 'accounts', 'fas fa-book');
        $this->addListView('ListSubcuenta', 'Subcuenta', 'subaccount');

        /// Disable columns
        $this->views['ListCuenta']->disableColumn('fiscal-exercise', true);
        $this->views['ListSubcuenta']->disableColumn('fiscal-exercise', true);
    }

    /**
     * Load view data procedure
     *
     * @param string                      $viewName
     * @param ExtendedController\EditView $view
     */
    protected function loadData($viewName, $view)
    {
        $codejercicio = $this->getViewModelValue('EditEjercicio', 'codejercicio');
        $where = [new DataBaseWhere('codejercicio', $codejercicio)];

        switch ($viewName) {
            case 'EditEjercicio':
                $code = $this->request->get('code');
                $view->loadData($code);
                break;

            case 'ListCuenta':
                $view->loadData(false, $where, ['codcuenta' => 'ASC']);
                break;

            case 'ListSubcuenta':
                $view->loadData(false, $where, ['codsubcuenta' => 'ASC']);
                break;
        }
    }

    /**
     * Run the controller after actions
     *
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'import-accounting':
                $this->importAccountingPlan();
                break;

            default:
                parent::execAfterAction($action);
        }
    }

    /**
     * Import AccountingPlan from any supported file type.
     *
     * @return bool
     */
    private function importAccountingPlan()
    {
        $accountingPlanImport = new AccountingPlanImport();
        $codejercicio = $this->getViewModelValue('EditEjercicio', 'codejercicio');
        $uploadFile = $this->request->files->get('accountingfile', false);
        if ($uploadFile === false) {
            $this->miniLog->alert($this->i18n->trans('file-not-found', ['%fileName%' => '']));
            return false;
        }

        switch ($uploadFile->getMimeType()) {
            case 'application/xml':
            case 'text/xml':
                $accountingPlanImport->importXML($uploadFile->getPathname(), $codejercicio);
                break;

            case 'text/csv':
            case 'text/plain':
                $accountingPlanImport->importCSV($uploadFile->getPathname(), $codejercicio);
                break;

            default:
                $this->miniLog->error($this->i18n->trans('file-not-supported'));
        }
    }
}
