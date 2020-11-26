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

use FacturaScripts\Core\Lib\ExtendedController\ListController;

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
     * Add an modal button for renumber entries
     *
     * @param string $viewName
     */
    protected function addRenumberButton(string $viewName)
    {
        $this->addButton($viewName, [
            'action' => 'renumber',
            'icon' => 'fas fa-sort-numeric-down',
            'label' => 'renumber-accounting',
            'type' => 'modal'
        ]);
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewsAccountEntries();
        $this->createViewsConcepts();
        $this->createViewsJournals();
    }

    /**
     * Add accounting entries tab
     *
     * @param string $viewName
     */
    protected function createViewsAccountEntries(string $viewName = 'ListAsiento')
    {
        $this->addView($viewName, 'Asiento', 'accounting-entries', 'fas fa-balance-scale');
        $this->addOrderBy($viewName, ['fecha', 'idasiento'], 'date', 2);
        $this->addOrderBy($viewName, ['numero', 'idasiento'], 'number');
        $this->addOrderBy($viewName, ['importe', 'idasiento'], 'amount');
        $this->addSearchFields($viewName, ['concepto', 'documento', 'numero']);

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
        $this->addLockButton($viewName);
        $this->addRenumberButton($viewName);
    }

    /**
     *
     * @param string $viewName
     */
    protected function createViewsConcepts(string $viewName = 'ListConceptoPartida')
    {
        $this->addView($viewName, 'ConceptoPartida', 'predefined-concepts', 'fas fa-indent');
        $this->addOrderBy($viewName, ['codconcepto'], 'code');
        $this->addOrderBy($viewName, ['descripcion'], 'description', 1);
        $this->addSearchFields($viewName, ['codconcepto', 'descripcion']);
    }

    /**
     *
     * @param string $viewName
     */
    protected function createViewsJournals(string $viewName = 'ListDiario')
    {
        $this->addView($viewName, 'Diario', 'journals', 'fas fa-book');
        $this->addOrderBy($viewName, ['iddiario'], 'code');
        $this->addOrderBy($viewName, ['descripcion'], 'description', 1);
        $this->addSearchFields($viewName, ['descripcion']);
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
                return $this->lockEntriesAction();

            case 'renumber':
                return $this->renumberAction();
        }

        return parent::execPreviousAction($action);
    }

    /**
     * 
     * @return bool
     */
    protected function lockEntriesAction()
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return true;
        }

        $codes = $this->request->request->get('code');
        $model = $this->views[$this->active]->model;
        if (false === \is_array($codes) || empty($model)) {
            $this->toolBox()->i18nLog()->warning('no-selected-item');
            return true;
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
                return true;
            }
        }

        $this->toolBox()->i18nLog()->notice('record-updated-correctly');
        $this->dataBase->commit();
        $model->clear();
        return true;
    }

    /**
     * 
     * @return bool
     */
    protected function renumberAction()
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return true;
        }

        $codejercicio = $this->request->request->get('exercise');
        if ($this->views['ListAsiento']->model->renumber($codejercicio)) {
            $this->toolBox()->i18nLog()->notice('renumber-accounting-ok');
        }
        return true;
    }
}
