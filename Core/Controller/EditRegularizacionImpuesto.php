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
use FacturaScripts\Core\Base\DivisaTools;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;

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
     * Formats the amount of the indicated field to currency
     *
     * @param string $field
     *
     * @return string
     */
    public function getAmount($field)
    {
        $divisaTools = new DivisaTools();
        return $divisaTools->format($this->{$field}, 2);
    }

    /**
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
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'vat-regularization';
        $pagedata['menu'] = 'accounting';
        $pagedata['icon'] = 'fas fa-map-signs';
        $pagedata['showonmenu'] = false;

        return $pagedata;
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
        $data = $this->requestGet(['field', 'source', 'fieldcode', 'fieldtitle', 'term', 'codejercicio']);
        $fields = $data['fieldcode'] . '|' . $data['fieldtitle'];
        $where = [
            new DataBaseWhere('codejercicio', $data['codejercicio']),
            new DataBaseWhere($fields, mb_strtolower($data['term'], 'UTF8'), 'LIKE')
        ];

        foreach ($this->codeModel->all($data['source'], $data['fieldcode'], $data['fieldtitle'], false, $where) as $row) {
            $results[] = ['key' => $row->code, 'value' => $row->description];
        }

        if (empty($results)) {
            $results[] = ['key' => null, 'value' => $this->i18n->trans('no-value')];
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
            if (in_array($row->codcuentaesp, ['IVAREX', 'IVAREP', 'IVARUE', 'IVARRE'])) {
                $this->sales += $row->cuotaiva + $row->cuotarecargo;
                continue;
            }

            if (in_array($row->codcuentaesp, ['IVASEX', 'IVASIM', 'IVASOP', 'IVASUE'])) {
                $this->purchases += $row->cuotaiva + $row->cuotarecargo;
            }
        }

        $this->total = $this->sales - $this->purchases - $this->previousBalance;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->addListView('ListPartidaImpuestoResumen', 'PartidaImpuestoResumen', 'summary', 'fas fa-list-alt');
        $this->addListView('ListPartidaImpuesto-1', 'PartidaImpuesto', 'purchases', 'fas fa-sign-in-alt');
        $this->addListView('ListPartidaImpuesto-2', 'PartidaImpuesto', 'sales', 'fas fa-sign-out-alt');
        $this->addListView('ListPartida', 'Partida', 'accounting-entry', 'fas fa-balance-scale');
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

    private function getListPartida($view)
    {
        $idasiento = $this->getViewModelValue('EditRegularizacionImpuesto', 'idasiento');
        if (!empty($idasiento)) {
            $where = [new DataBaseWhere('idasiento', $idasiento)];
            $view->loadData($where, ['orden' => 'ASC']);
        }
    }

    private function getListPartidaImpuesto1($view)
    {
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
    }

    private function getListPartidaImpuesto2($view)
    {
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
    }

    private function getListPartidaImpuestoResumen($view)
    {
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
            $this->calculateAmounts($view->cursor);
        }
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
                break;

            case 'ListPartida':
                $this->getListPartida($view);
                break;

            case 'ListPartidaImpuestoResumen':
                $this->getListPartidaImpuestoResumen($view);
                break;

            case 'ListPartidaImpuesto-1':
                $this->getListPartidaImpuesto1($view);
                break;

            case 'ListPartidaImpuesto-2':
                $this->getListPartidaImpuesto2($view);
                break;
        }
    }
}
