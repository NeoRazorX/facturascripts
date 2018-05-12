<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\ExtendedController;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\DivisaTools;
use FacturaScripts\Core\Model\CodeModel;

/**
 * Controller to list the items in the RegularizacionImpuesto model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditRegularizacionImpuesto extends ExtendedController\PanelController
{
    /**
     * Amount to be offset from previous regularization
     *
     * @var float
     */
    public $previousBalance;

    /**
     * Sum of all sales
     *
     * @var float
     */
    public $sales;

    /**
     * Sum of all purchases
     *
     * @var float
     */
    public $purchases;

    /**
     * total amount of regularization: (sales - purchases - previousBalance)
     *
     * @var float
     */
    public $total;

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'vat-regularization';
        $pagedata['menu'] = 'accounting';
        $pagedata['icon'] = 'fa-map-signs';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addEditView('EditRegularizacionImpuesto', 'RegularizacionImpuesto', 'vat-regularization', 'fa-map-signs');
        $this->addListView('ListPartidaImpuestoResumen', 'PartidaImpuestoResumen', 'summary', 'fa-list-alt');
        $this->addListView('ListPartidaImpuesto-1', 'PartidaImpuesto', 'purchases', 'fa-sign-in');
        $this->addListView('ListPartidaImpuesto-2', 'PartidaImpuesto', 'sales', 'fa-sign-out');
        $this->addListView('ListPartida', 'Partida', 'accounting-entry', 'fa-balance-scale');
        $this->setTabsPosition('bottom');
    }

    /**
     * Load data view procedure
     *
     * @param string                      $viewName
     * @param ExtendedController\BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'EditRegularizacionImpuesto':
                $code = $this->request->get('code');
                $view->loadData($code);
                break;

            case 'ListPartida':
                $idasiento = $this->getViewModelValue('EditRegularizacionImpuesto', 'idasiento');
                if (!empty($idasiento)) {
                    $where = [new DataBaseWhere('idasiento', $idasiento)];
                    $view->loadData($where, ['orden' => 'ASC']);
                }
                break;

            case 'ListPartidaImpuestoResumen':
                $id = $this->getViewModelValue('EditRegularizacionImpuesto', 'idregularizacion');
                if (!empty($id)) {
                    $exercise = $this->getViewModelValue('EditRegularizacionImpuesto', 'codejercicio');
                    $startDate = $this->getViewModelValue('EditRegularizacionImpuesto', 'fechainicio');
                    $endDate = $this->getViewModelValue('EditRegularizacionImpuesto', 'fechafin');
                    $where = [
                        new DataBaseWhere('asientos.codejercicio', $exercise),
                        new DataBaseWhere('asientos.fecha', $startDate, '>='),
                        new DataBaseWhere('asientos.fecha', $endDate, '<='),
                        new DataBaseWhere('subcuentas.codcuentaesp', 'IVAREX,IVAREP,IVARUE,IVARRE,IVASEX,IVASIM,IVASOP,IVASUE', 'IN')
                    ];
                    $orderby = [
                        'cuentasesp.descripcion' => 'ASC',
                        'subcuentas.codimpuesto' => 'ASC',
                        'partidas.iva' => 'ASC',
                        'partidas.recargo' => 'ASC'
                    ];
                    $view->loadData(false, $where, $orderby);
                    $this->calculateAmounts($view->getCursor());
                }
                break;

            case 'ListPartidaImpuesto-1':
                $id = $this->getViewModelValue('EditRegularizacionImpuesto', 'idregularizacion');
                if (!empty($id)) {
                    $exercise = $this->getViewModelValue('EditRegularizacionImpuesto', 'codejercicio');
                    $startDate = $this->getViewModelValue('EditRegularizacionImpuesto', 'fechainicio');
                    $endDate = $this->getViewModelValue('EditRegularizacionImpuesto', 'fechafin');
                    $where = [
                        new DataBaseWhere('asientos.codejercicio', $exercise),
                        new DataBaseWhere('asientos.fecha', $startDate, '>='),
                        new DataBaseWhere('asientos.fecha', $endDate, '<='),
                        new DataBaseWhere('subcuentas.codcuentaesp', 'IVASEX,IVASIM,IVASOP,IVASUE', 'IN')
                    ];
                    $view->loadData(false, $where, ['partidas.codserie' => 'ASC', 'partidas.factura' => 'ASC']);
                }
                break;

            case 'ListPartidaImpuesto-2':
                $id = $this->getViewModelValue('EditRegularizacionImpuesto', 'idregularizacion');
                if (!empty($id)) {
                    $exercise = $this->getViewModelValue('EditRegularizacionImpuesto', 'codejercicio');
                    $startDate = $this->getViewModelValue('EditRegularizacionImpuesto', 'fechainicio');
                    $endDate = $this->getViewModelValue('EditRegularizacionImpuesto', 'fechafin');
                    $where = [
                        new DataBaseWhere('asientos.codejercicio', $exercise),
                        new DataBaseWhere('asientos.fecha', $startDate, '>='),
                        new DataBaseWhere('asientos.fecha', $endDate, '<='),
                        new DataBaseWhere('subcuentas.codcuentaesp', 'IVAREX,IVAREP,IVARUE,IVARRE', 'IN')
                    ];
                    $view->loadData(false, $where, ['partidas.codserie' => 'ASC', 'partidas.factura' => 'ASC']);
                }
                break;
        }
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
                /// TODO: Create accounting entry
                return true;

            default:
                return parent::execPreviousAction($action);
        }
    }

    /**
     * Run the controller after actions.
     *
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'insert':
                /// Load default values for new record
                $this->views['EditRegularizacionImpuesto']->model->loadNextPeriod();
                break;

            default:
                parent::execAfterAction($action);
        }
    }

    /**
     * Run the autocomplete action with exercise filter
     * Returns a JSON string for the searched values.
     *
     * @return array
     */
    protected function autocompleteAction(): array
    {
        $results = [];
        $data = $this->requestGet(['source', 'field', 'title', 'term']);
        $fields = $data['field'] . '|' . $data['title'];
        $where = [
            new DataBaseWhere('codejercicio', '2018'),
            new DataBaseWhere($fields, mb_strtolower($data['term']), 'LIKE')
        ];

        foreach (CodeModel::all($data['source'], $data['field'], $data['title'], false, $where) as $row) {
            $results[] = ['key' => $row->code, 'value' => $row->description];
        }
        return $results;
    }

    /**
     * Calculates the amounts for the different sections of the regularization
     *
     * @param PartidaImpuestoResumen[] $data
     */
    private function calculateAmounts($data)
    {
        /// Init totals values
        $this->previousBalance = 0.00;    /// TODO: Calculate previous balance from special account
        $this->sales = 0.00;
        $this->purchases = 0.00;

        foreach ($data as $row) {
            if (in_array($row->codcuentaesp, ['IVAREX','IVAREP','IVARUE','IVARRE'])) {
                $this->sales += $row->cuotaiva + $row->cuotarecargo;
                continue;
            }

            if (in_array($row->codcuentaesp, ['IVASEX','IVASIM','IVASOP','IVASUE'])) {
                $this->purchases += $row->cuotaiva + $row->cuotarecargo;
            }
        }

        $this->total = $this->sales - $this->purchases - $this->previousBalance;
    }

    /**
     * Formats the amount of the indicated field to currency
     *
     * @param string $field
     * @return string
     */
    public function getAmount($field)
    {
        $divisaTools = new DivisaTools();
        return $divisaTools->format($this->{$field}, 2);
    }
}
