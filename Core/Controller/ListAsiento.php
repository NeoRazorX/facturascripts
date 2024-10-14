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
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Tools;

/**
 * Controller to list the items in the Asiento model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ListAsiento extends ListController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'accounting-entries';
        $data['icon'] = 'fa-solid fa-balance-scale';
        return $data;
    }

    /**
     * Add an action button for lock entries list
     *
     * @param string $viewName
     */
    protected function addLockButton(string $viewName): void
    {
        $this->addButton($viewName, [
            'action' => 'lock-entries',
            'confirm' => true,
            'icon' => 'fa-solid fa-lock',
            'label' => 'lock-entry'
        ]);
    }

    /**
     * Adds a modal button for renumber entries
     *
     * @param string $viewName
     */
    protected function addRenumberButton(string $viewName): void
    {
        $this->addButton($viewName, [
            'action' => 'renumber',
            'icon' => 'fa-solid fa-sort-numeric-down',
            'label' => 'renumber',
            'type' => 'modal'
        ]);
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewsAccountEntries();
        $this->createViewsNotBalanced();
        $this->createViewsConcepts();
        $this->createViewsJournals();
    }

    protected function createViewsAccountEntries(string $viewName = 'ListAsiento'): void
    {
        $this->addView($viewName, 'Asiento', 'accounting-entries', 'fa-solid fa-balance-scale')
            ->addSearchFields(['concepto', 'documento', 'CAST(numero AS char(255))'])
            ->addOrderBy(['fecha', 'numero'], 'date', 2)
            ->addOrderBy(['numero', 'idasiento'], 'number')
            ->addOrderBy(['importe', 'idasiento'], 'amount');

        // filtros
        $this->addFilterPeriod($viewName, 'date', 'period', 'fecha')
            ->addFilterNumber('min-total', 'amount', 'importe', '>=')
            ->addFilterNumber('max-total', 'amount', 'importe', '<=')
            ->addFilterCheckbox('editable');

        // filtro de operación
        $operaciones = [
            '' => '------',
            Asiento::OPERATION_OPENING => Tools::lang()->trans('opening-operation'),
            Asiento::OPERATION_CLOSING => Tools::lang()->trans('closing-operation'),
            Asiento::OPERATION_REGULARIZATION => Tools::lang()->trans('regularization-operation')
        ];
        $this->addFilterSelect($viewName, 'operacion', 'operation', 'operacion', $operaciones);

        $selectCompany = Empresas::codeModel();
        if (count($selectCompany) > 2) {
            $this->addFilterSelect($viewName, 'idempresa', 'company', 'idempresa', $selectCompany);
        }

        $selectExercise = $this->getSelectExercise();
        if (count($selectExercise) > 2) {
            $this->addFilterSelect($viewName, 'codejercicio', 'exercise', 'codejercicio', $selectExercise);
        }

        $selectJournals = $this->codeModel->all('diarios', 'iddiario', 'descripcion');
        $this->addFilterSelect($viewName, 'iddiario', 'journals', 'iddiario', $selectJournals);

        $selectChannel = $this->codeModel->all('asientos', 'canal', 'canal');
        if (count($selectChannel) > 2) {
            $this->addFilterSelect($viewName, 'canal', 'channel', 'canal', $selectChannel);
        }

        // botones
        if ($this->permissions->allowUpdate) {
            $this->addLockButton($viewName);
            $this->addRenumberButton($viewName);
        }
    }

    protected function createViewsConcepts(string $viewName = 'ListConceptoPartida'): void
    {
        $this->addView($viewName, 'ConceptoPartida', 'predefined-concepts', 'fa-solid fa-indent')
            ->addSearchFields(['codconcepto', 'descripcion'])
            ->addOrderBy(['codconcepto'], 'code')
            ->addOrderBy(['descripcion'], 'description', 1);
    }

    protected function createViewsJournals(string $viewName = 'ListDiario'): void
    {
        $this->addView($viewName, 'Diario', 'journals', 'fa-solid fa-book')
            ->addSearchFields(['descripcion'])
            ->addOrderBy(['iddiario'], 'code')
            ->addOrderBy(['descripcion'], 'description', 1);
    }

    protected function createViewsNotBalanced(string $viewName = 'ListAsiento-not'): void
    {
        $ids = [];
        $sql = 'SELECT partidas.idasiento, ABS(SUM(partidas.debe) - SUM(partidas.haber))'
            . ' FROM partidas GROUP BY 1 HAVING ROUND(ABS(SUM(partidas.debe) - SUM(partidas.haber)), 2) >= 0.01';

        if (Tools::config('db_type') === 'postgresql') {
            $sql = 'SELECT partidas.idasiento, ABS(SUM(partidas.debe) - SUM(partidas.haber))'
                . ' FROM partidas GROUP BY 1 HAVING ABS(SUM(partidas.debe) - SUM(partidas.haber)) >= 0.01';
        }

        foreach ($this->dataBase->select($sql) as $row) {
            $ids[] = $row['idasiento'];
        }
        if (empty($ids)) {
            return;
        }

        $this->addView($viewName, 'Asiento', 'unbalance', 'fa-solid fa-exclamation-circle')
            ->addSearchFields(['concepto', 'documento', 'CAST(numero AS char(255))'])
            ->addOrderBy(['fecha', 'idasiento'], 'date', 2)
            ->addOrderBy(['numero', 'idasiento'], 'number')
            ->addOrderBy(['importe', 'idasiento'], 'amount');

        // filter
        $this->addFilterSelectWhere($viewName, 'status', [
            [
                'label' => Tools::lang()->trans('unbalance'),
                'where' => [new DataBaseWhere('idasiento', join(',', $ids), 'IN')]
            ]
        ]);
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
            case 'lock-entries':
                $this->lockEntriesAction();
                return true;

            case 'renumber':
                $this->renumberAction();
                return true;
        }

        return parent::execPreviousAction($action);
    }

    protected function lockEntriesAction(): void
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return;
        } elseif (false === $this->validateFormToken()) {
            return;
        }

        $codes = $this->request->request->getArray('codes');
        $model = $this->views[$this->active]->model;
        if (false === is_array($codes) || empty($model)) {
            Tools::log()->warning('no-selected-item');
            return;
        }

        $this->dataBase->beginTransaction();
        foreach ($codes as $code) {
            if (false === $model->loadFromCode($code)) {
                Tools::log()->error('record-not-found');
                continue;
            } elseif (false === $model->editable) {
                continue;
            }

            $model->editable = false;
            if (false === $model->save()) {
                Tools::log()->error('record-save-error');
                $this->dataBase->rollback();
                return;
            }
        }

        Tools::log()->notice('record-updated-correctly');
        $this->dataBase->commit();
        $model->clear();
    }

    protected function renumberAction(): void
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return;
        } elseif (false === $this->validateFormToken()) {
            return;
        }

        $this->dataBase->beginTransaction();
        $codejercicio = $this->request->request->get('exercise');
        if ($this->views['ListAsiento']->model->renumber($codejercicio)) {
            Tools::log()->notice('renumber-accounting-ok');
            $this->dataBase->commit();
            return;
        }

        $this->dataBase->rollback();
        Tools::log()->error('record-save-error');
    }

    private function getSelectExercise(): array
    {
        $companyFilter = $this->request->request->get('filteridempresa', 0);
        $exerciseFilter = $this->request->request->get('filtercodejercicio', '');
        $where = empty($companyFilter) ? [] : [new DataBaseWhere('idempresa', $companyFilter)];
        $result = $this->codeModel->all('ejercicios', 'codejercicio', 'nombre', true, $where);
        if (empty($exerciseFilter)) {
            return $result;
        }

        // check if the selected exercise is in the list
        foreach ($result as $exercise) {
            if ($exerciseFilter === $exercise->code) {
                return $result;
            }
        }

        // remove exercise filter if it is not in the list
        $this->request->request->set('filtercodejercicio', '');
        return $result;
    }
}
