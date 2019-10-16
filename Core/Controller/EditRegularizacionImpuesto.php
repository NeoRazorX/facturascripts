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
use FacturaScripts\Dinamic\Lib\Accounting\VatRegularizationToAccounting;
use FacturaScripts\Dinamic\Lib\SubAccountTools;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\ModelView\PartidaImpuestoResumen;
use FacturaScripts\Dinamic\Model\RegularizacionImpuesto;

/**
 * Controller to list the items in the RegularizacionImpuesto model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Artex Trading sa             <jcuello@artextrading.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class EditRegularizacionImpuesto extends EditController
{

    /**
     * Amount to be offset from previous regularization
     *
     * @var float
     */
    public $previousBalance;

    /**
     * Sum of all purchases
     *
     * @var float
     */
    public $purchases;

    /**
     * Sum of all sales
     *
     * @var float
     */
    public $sales;

    /**
     * total amount of regularization: (sales - purchases - previousBalance)
     *
     * @var float
     */
    public $total;

    /**
     * Returns the class name of the model to use in the editView.
     *
     * @return string
     */
    public function getModelClassName()
    {
        return 'RegularizacionImpuesto';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'vat-regularization';
        $data['icon'] = 'fas fa-balance-scale-right';
        return $data;
    }

    /**
     * Add to the view container the view with the lines of
     * the accounting entry generated.
     *
     * @param string $viewName
     */
    protected function addEntryLineView($viewName = 'ListPartida')
    {
        $this->addListView($viewName, 'Partida', 'accounting-entry', 'fas fa-balance-scale');
        $this->disableButtons($viewName);
    }

    /**
     * Add to the view container the view with the list of sales documents included.
     *
     * @param string $viewName
     * @param string $caption
     * @param string $icon
     */
    protected function addTaxLineView($viewName, $caption, $icon)
    {
        $this->addListView($viewName, 'ModelView\PartidaImpuesto', $caption, $icon);
        $this->disableButtons($viewName);
    }

    /**
     * Add to container views with the summary view or total liquidation.
     *
     * @param string $viewName
     */
    protected function addTaxSummaryView($viewName = 'ListPartidaImpuestoResumen')
    {
        $this->addListView($viewName, 'ModelView\PartidaImpuestoResumen', 'summary', 'fas fa-list-alt');
        $this->disableButtons($viewName);
    }

    /**
     * Run the autocomplete action with exercise filter
     *
     * @return array
     */
    protected function autocompleteAction(): array
    {
        $keys = $this->requestGet(['field', 'source', 'fieldcode', 'fieldtitle', 'term', 'codejercicio']);
        return SubAccountTools::autocompleteAction($keys);
    }

    /**
     * Calculates the amounts for the different sections of the regularization
     *
     * @param PartidaImpuestoResumen[] $data
     */
    protected function calculateAmounts($data)
    {
        /// Init totals values
        $this->previousBalance = 0.00;    /// TODO: Calculate previous balance from generated accounting entry
        $this->sales = 0.00;
        $this->purchases = 0.00;

        $subAccountTools = new SubAccountTools();
        foreach ($data as $row) {
            if ($subAccountTools->isOutputTax($row->codcuentaesp)) {
                $this->sales += $row->cuotaiva + $row->cuotarecargo;
                continue;
            }

            if ($subAccountTools->isInputTax($row->codcuentaesp)) {
                $this->purchases += $row->cuotaiva + $row->cuotarecargo;
            }
        }

        $this->total = $this->sales - $this->purchases - $this->previousBalance;
    }

    /**
     * Generates the accounting entry for the settlement.
     *
     * @param int $code
     */
    protected function createAccountingEntry($code)
    {
        $document = new RegularizacionImpuesto();
        if ($document->loadFromCode($code) && empty($document->idasiento)) {
            $accounting = new VatRegularizationToAccounting();
            $accounting->generate($document);
            if (!empty($document->idasiento)) {
                $document->save();
            }
        }
    }

    /**
     * Add the view set.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->addTaxSummaryView();
        $this->addTaxLineView('ListPartidaImpuesto-1', 'purchases', 'fas fa-sign-in-alt');
        $this->addTaxLineView('ListPartidaImpuesto-2', 'sales', 'fas fa-sign-out-alt');
        $this->addEntryLineView();
    }

    /**
     * Disable the add and remove buttons from the indicated view.
     *
     * @param string $viewName
     */
    protected function disableButtons($viewName)
    {
        $this->views[$viewName]->settings['btnDelete'] = false;
        $this->views[$viewName]->settings['btnNew'] = false;
    }

    /**
     * 
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        if ($action === 'export') {
            $this->exportManager->setOrientation('landscape');
        }

        parent::execAfterAction($action);
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'create-accounting-entry':
                $code = $this->request->get('code');
                $this->createAccountingEntry($code);
                return true;
        }

        return parent::execPreviousAction($action);
    }

    /**
     * Load data of Partida view if master view has data.
     *
     * @param BaseView $view
     */
    protected function getListPartida($view)
    {
        $idasiento = $this->getViewModelValue('EditRegularizacionImpuesto', 'idasiento');
        if (!empty($idasiento)) {
            $where = [new DataBaseWhere('idasiento', $idasiento)];
            $view->loadData(false, $where, ['orden' => 'ASC']);
        }
    }

    /**
     * Load data of PartidaImpuesto if master view has data.
     *
     * @param BaseView $view
     * @param int      $group
     */
    protected function getListPartidaImpuesto($view, $group)
    {
        $id = $this->getViewModelValue('EditRegularizacionImpuesto', 'idregiva');
        if (!empty($id)) {
            $where = $this->getPartidaImpuestoWhere($group);
            $view->loadData(false, $where, ['partidas.codserie' => 'ASC', 'partidas.factura' => 'ASC']);
        }
    }

    /**
     * Load data of PartidaImpuestoResumen if master view has data.
     *
     * @param BaseView $view
     */
    protected function getListPartidaImpuestoResumen($view)
    {
        $id = $this->getViewModelValue('EditRegularizacionImpuesto', 'idregiva');
        if (!empty($id)) {
            $orderby = [
                'cuentasesp.descripcion' => 'ASC',
                'partidas.iva' => 'ASC',
                'partidas.recargo' => 'ASC'
            ];
            $where = $this->getPartidaImpuestoWhere(SubAccountTools::SPECIAL_GROUP_TAX_ALL);
            $view->loadData(false, $where, $orderby);
            $this->calculateAmounts($view->cursor);
        }
    }

    /**
     * Get DataBaseWhere filter for tax group
     *
     * @param int $group
     *
     * @return DataBaseWhere[]
     */
    protected function getPartidaImpuestoWhere($group)
    {
        $subAccountTools = new SubAccountTools();
        $idasiento = $this->getViewModelValue('EditRegularizacionImpuesto', 'idasiento');
        $exercise = $this->getViewModelValue('EditRegularizacionImpuesto', 'codejercicio');
        $startDate = $this->getViewModelValue('EditRegularizacionImpuesto', 'fechainicio');
        $endDate = $this->getViewModelValue('EditRegularizacionImpuesto', 'fechafin');
        return [
            new DataBaseWhere('asientos.idasiento', $idasiento, '!='),
            new DataBaseWhere('asientos.codejercicio', $exercise),
            new DataBaseWhere('asientos.fecha', $startDate, '>='),
            new DataBaseWhere('asientos.fecha', $endDate, '<='),
            $subAccountTools->whereForSpecialAccounts('COALESCE(subcuentas.codcuentaesp, cuentas.codcuentaesp)', $group)
        ];
    }

    /**
     * Load data view procedure
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'EditRegularizacionImpuesto':
                parent::loadData($viewName, $view);
                $this->setCustomWidgetValues($view);
                break;

            case 'ListPartida':
                $this->getListPartida($view);
                break;

            case 'ListPartidaImpuestoResumen':
                $this->getListPartidaImpuestoResumen($view);
                break;

            case 'ListPartidaImpuesto-1':
                $this->getListPartidaImpuesto($view, SubAccountTools::SPECIAL_GROUP_TAX_INPUT);
                break;

            case 'ListPartidaImpuesto-2':
                $this->getListPartidaImpuesto($view, SubAccountTools::SPECIAL_GROUP_TAX_OUTPUT);
                break;
        }
    }

    /**
     * Configure and initialize main view widgets.
     *
     * @param BaseView $view
     */
    protected function setCustomWidgetValues($view)
    {
        $openExercises = [];
        $exerciseModel = new Ejercicio();
        foreach ($exerciseModel->all([], ['fechainicio' => 'DESC']) as $exercise) {
            if ($exercise->isOpened()) {
                $openExercises[$exercise->codejercicio] = $exercise->nombre;
            }
        }

        $columnExercise = $view->columnForName('exercise');
        if ($columnExercise) {
            $columnExercise->widget->setValuesFromArrayKeys($openExercises);
        }

        /// Model exists?
        if (!$view->model->exists()) {
            $view->disableColumn('tax-credit-account', false, 'true');
            $view->disableColumn('tax-debit-account', false, 'true');
        }
    }
}
