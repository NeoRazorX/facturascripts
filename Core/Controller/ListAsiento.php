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
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Dinamic\Model\Asiento;

/**
 * Controller to list the items in the Asiento model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ListAsiento extends ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'accounting-entries';
        $data['icon'] = 'fas fa-balance-scale';
        return $data;
    }

    /**
     * Add an action button for lock entries list
     *
     * @param string $viewName
     */
    protected function addLockButton($viewName)
    {
        $newButton = [
            'action' => 'lock-entries',
            'color' => 'warning',
            'icon' => 'fas fa-lock',
            'label' => 'lock-entries',
            'type' => 'action',
        ];
        $this->addButton($viewName, $newButton);
    }

    /**
     * Add an modal button for renumber entries
     *
     * @param string $viewName
     */
    protected function addRenumberButton($viewName)
    {
        $newButton = [
            'action' => 'renumber',
            'color' => 'warning',
            'icon' => 'fas fa-sort-numeric-down',
            'label' => 'renumber-accounting',
            'type' => 'modal',
        ];
        $this->addButton($viewName, $newButton);
    }

    /**
     * Blocks the list of indicated accounting entries
     *
     * @param array|int $codeList
     * @return boolean
     */
    protected function changeLockStatus($codeList)
    {
        $codes = is_array($codeList) ? implode(',', $codeList) : $codeList;
        $where = [ new DataBaseWhere('idasiento', $codes, 'IN') ];

        $accounting = new Asiento();
        foreach ($accounting->all($where) as $row) {
            if ($row->editable) {
                $row->editable = false;
                $row->save();
            }
        }
        return true;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewAccountEntries();
        $this->createViewConcepts();
        $this->createViewJournals();
    }

    /**
     * Add accounting entries tab
     *
     * @param string $viewName
     */
    protected function createViewAccountEntries($viewName = 'ListAsiento')
    {
        $this->addView($viewName, 'Asiento', 'accounting-entries', 'fas fa-balance-scale');
        $this->addSearchFields($viewName, ['numero', 'concepto']);
        $this->addOrderBy($viewName, ['fecha', 'idasiento'], 'date', 2);
        $this->addOrderBy($viewName, ['numero', 'idasiento'], 'number');
        $this->addOrderBy($viewName, ['importe', 'idasiento'], 'amount');

        /// filters
        $this->addFilterPeriod($viewName, 'date', 'period', 'fecha');
        $this->addFilterNumber($viewName, 'min-total', 'amount', 'importe', '>=');
        $this->addFilterNumber($viewName, 'max-total', 'amount', 'importe', '<=');

        $selectCompany = $this->codeModel->all('empresas', 'idempresa', 'nombrecorto');
        $this->addFilterSelect($viewName, 'idempresa', 'company', 'idempresa', $selectCompany);

        $selectExercise = $this->codeModel->all('ejercicios', 'codejercicio', 'nombre');
        $this->addFilterSelect($viewName, 'codejercicio', 'exercise', 'codejercicio', $selectExercise);

        $selectJournals = $this->codeModel->all('diarios', 'iddiario', 'descripcion');
        $this->addFilterSelect($viewName, 'iddiario', 'journals', 'iddiario', $selectJournals);

        $selectChannel = $this->codeModel->all('asientos', 'canal', 'canal');
        $this->addFilterSelect($viewName, 'canal', 'channel', 'canal', $selectChannel);

        $this->addFilterCheckbox($viewName, 'editable');

        /// buttons
        $this->addRenumberButton($viewName);
        $this->addLockButton($viewName);
    }

    /**
     *
     * @param string $viewName
     */
    protected function createViewConcepts($viewName = 'ListConceptoPartida')
    {
        $this->addView($viewName, 'ConceptoPartida', 'predefined-concepts', 'fas fa-indent');
        $this->addSearchFields($viewName, ['codconcepto', 'descripcion']);
        $this->addOrderBy($viewName, ['codconcepto'], 'code');
        $this->addOrderBy($viewName, ['descripcion'], 'description');
    }

    /**
     *
     * @param string $viewName
     */
    protected function createViewJournals($viewName = 'ListDiario')
    {
        $this->addView($viewName, 'Diario', 'journals', 'fas fa-book');
        $this->addSearchFields($viewName, ['descripcion']);
        $this->addOrderBy($viewName, ['iddiario'], 'code');
        $this->addOrderBy($viewName, ['descripcion'], 'description');
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
            case 'renumber':
                $codejercicio = $this->request->request->get('exercise');
                if ($this->views['ListAsiento']->model->renumber($codejercicio)) {
                    $this->toolBox()->i18nLog()->notice('renumber-accounting-ok');
                }
                return true;

            case 'lock-entries':
                $codeList = $this->request->get('code');
                if ($this->changeLockStatus($codeList)) {
                    $this->toolBox()->i18nLog()->notice('lock-entries-ok');
                }
                return true;
        }

        return parent::execPreviousAction($action);
    }
}
