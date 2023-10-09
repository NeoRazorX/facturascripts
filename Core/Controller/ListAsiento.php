<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\DataSrc\Ejercicios;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Model\Asiento;

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
        $data['icon'] = 'fas fa-balance-scale';
        return $data;
    }

    /**
     * Add an action button for lock entries list
     *
     * @param string $viewName
     */
    protected function addLockButton(string $viewName)
    {
        $this->addButton($viewName, [
            'action' => 'lock-entries',
            'confirm' => true,
            'icon' => 'fas fa-lock',
            'label' => 'lock-entry'
        ]);
    }

    /**
     * Adds a modal button for renumber entries
     *
     * @param string $viewName
     */
    protected function addRenumberButton(string $viewName)
    {
        $this->addButton($viewName, [
            'action' => 'renumber',
            'icon' => 'fas fa-sort-numeric-down',
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

    protected function createViewsAccountEntries(string $viewName = 'ListAsiento')
    {
        $this->addView($viewName, 'Asiento', 'accounting-entries', 'fas fa-balance-scale');
        $this->addOrderBy($viewName, ['fecha', 'numero'], 'date', 2);
        $this->addOrderBy($viewName, ['numero', 'idasiento'], 'number');
        $this->addOrderBy($viewName, ['importe', 'idasiento'], 'amount');
        $this->addSearchFields($viewName, ['concepto', 'documento', 'CAST(numero AS char(255))']);

        // filtros
        $this->addFilterPeriod($viewName, 'date', 'period', 'fecha');
        $this->addFilterNumber($viewName, 'min-total', 'amount', 'importe', '>=');
        $this->addFilterNumber($viewName, 'max-total', 'amount', 'importe', '<=');
        $this->addFilterCheckbox($viewName, 'editable');

        // filtro de operación
        $operaciones = [
            '' => '------',
            Asiento::OPERATION_OPENING => self::toolBox()::i18n()->trans('opening-operation'),
            Asiento::OPERATION_CLOSING => self::toolBox()::i18n()->trans('closing-operation'),
            Asiento::OPERATION_REGULARIZATION => self::toolBox()::i18n()->trans('regularization-operation')
        ];
        $this->addFilterSelect($viewName, 'operacion', 'operation', 'operacion', $operaciones);

        $selectCompany = Empresas::codeModel();
        if (count($selectCompany) > 2) {
            $this->addFilterSelect($viewName, 'idempresa', 'company', 'idempresa', $selectCompany);
        }

        $selectExercise = Ejercicios::codeModel();
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

    protected function createViewsConcepts(string $viewName = 'ListConceptoPartida')
    {
        $this->addView($viewName, 'ConceptoPartida', 'predefined-concepts', 'fas fa-indent');
        $this->addOrderBy($viewName, ['codconcepto'], 'code');
        $this->addOrderBy($viewName, ['descripcion'], 'description', 1);
        $this->addSearchFields($viewName, ['codconcepto', 'descripcion']);
    }

    protected function createViewsJournals(string $viewName = 'ListDiario')
    {
        $this->addView($viewName, 'Diario', 'journals', 'fas fa-book');
        $this->addOrderBy($viewName, ['iddiario'], 'code');
        $this->addOrderBy($viewName, ['descripcion'], 'description', 1);
        $this->addSearchFields($viewName, ['descripcion']);
    }

    protected function createViewsNotBalanced(string $viewName = 'ListAsiento-not')
    {
        $idasientos = [];
        $sql = 'SELECT partidas.idasiento, ABS(SUM(partidas.debe) - SUM(partidas.haber))'
            . ' FROM partidas GROUP BY 1 HAVING ABS(SUM(partidas.debe) - SUM(partidas.haber)) >= 0.01';
        foreach ($this->dataBase->select($sql) as $row) {
            $idasientos[] = $row['idasiento'];
        }

        if (count($idasientos) > 0) {
            $this->addView($viewName, 'Asiento', 'unbalance', 'fas fa-exclamation-circle');
            $this->addOrderBy($viewName, ['fecha', 'idasiento'], 'date', 2);
            $this->addOrderBy($viewName, ['numero', 'idasiento'], 'number');
            $this->addOrderBy($viewName, ['importe', 'idasiento'], 'amount');
            $this->addSearchFields($viewName, ['concepto', 'documento', 'CAST(numero AS char(255))']);

            // filter
            $this->addFilterSelectWhere($viewName, 'status', [
                [
                    'label' => $this->toolBox()->i18n()->trans('unbalance'),
                    'where' => [new DataBaseWhere('idasiento', join(',', $idasientos), 'IN')]
                ]
            ]);
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
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return;
        } elseif (false === $this->validateFormToken()) {
            return;
        }

        $codes = $this->request->request->get('code');
        $model = $this->views[$this->active]->model;
        if (false === is_array($codes) || empty($model)) {
            $this->toolBox()->i18nLog()->warning('no-selected-item');
            return;
        }

        $this->dataBase->beginTransaction();
        foreach ($codes as $code) {
            if (false === $model->loadFromCode($code)) {
                $this->toolBox()->i18nLog()->error('record-not-found');
                continue;
            } elseif (false === $model->editable) {
                continue;
            }

            $model->editable = false;
            if (false === $model->save()) {
                $this->toolBox()->i18nLog()->error('record-save-error');
                $this->dataBase->rollback();
                return;
            }
        }

        $this->toolBox()->i18nLog()->notice('record-updated-correctly');
        $this->dataBase->commit();
        $model->clear();
    }

    protected function renumberAction(): void
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return;
        } elseif (false === $this->validateFormToken()) {
            return;
        }

        $this->dataBase->beginTransaction();
        $codejercicio = $this->request->request->get('exercise');
        if ($this->views['ListAsiento']->model->renumber($codejercicio)) {
            $this->toolBox()->i18nLog()->notice('renumber-accounting-ok');
            $this->dataBase->commit();
            return;
        }

        $this->dataBase->rollback();
        $this->toolBox()->i18nLog()->error('record-save-error');
    }
}
