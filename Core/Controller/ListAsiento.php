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
     * @param string $name
     */
    private function createViewAccountEntries($name = 'ListAsiento')
    {
        $this->addView($name, 'Asiento', 'accounting-entries', 'fas fa-balance-scale');
        $this->addSearchFields($name, ['CAST(numero AS CHAR(10))', 'concepto']);
        $this->addOrderBy($name, ['fecha', 'idasiento'], 'date', 2);
        $this->addOrderBy($name, ['numero', 'idasiento'], 'number');
        $this->addOrderBy($name, ['importe', 'idasiento'], 'ammount');

        /// filters
        $this->addFilterPeriod($name, 'date', 'period', 'fecha');
        $this->addFilterNumber($name, 'min-total', 'amount', 'importe', '>=');
        $this->addFilterNumber($name, 'max-total', 'amount', 'importe', '<=');

        $selectCompany = $this->codeModel->all('empresas', 'idempresa', 'nombrecorto');
        $this->addFilterSelect($name, 'idempresa', 'company', 'idempresa', $selectCompany);

        $selectExercise = $this->codeModel->all('ejercicios', 'codejercicio', 'nombre');
        $this->addFilterSelect($name, 'codejercicio', 'exercise', 'codejercicio', $selectExercise);

        $selectJournals = $this->codeModel->all('diarios', 'iddiario', 'descripcion');
        $this->addFilterSelect($name, 'iddiario', 'journals', 'iddiario', $selectJournals);

        $this->addFilterNumber($name, 'canal', 'channel', 'canal', '=');

        /// buttons
        $newButton = [
            'action' => 'renumber',
            'color' => 'warning',
            'confirm' => true,
            'icon' => 'fas fa-sort-numeric-down',
            'label' => 'renumber-accounting',
            'type' => 'modal',
        ];
        $this->addButton($name, $newButton);
    }

    /**
     * 
     * @param string $name
     */
    private function createViewConcepts($name = 'ListConceptoPartida')
    {
        $this->addView($name, 'ConceptoPartida', 'predefined-concepts', 'fas fa-indent');
        $this->addSearchFields($name, ['codconcepto', 'descripcion']);
        $this->addOrderBy($name, ['codconcepto'], 'code');
        $this->addOrderBy($name, ['descripcion'], 'description');
    }

    /**
     * 
     * @param string $name
     */
    private function createViewJournals($name = 'ListDiario')
    {
        $this->addView($name, 'Diario', 'journals', 'fas fa-book');
        $this->addSearchFields($name, ['iddiario', 'descripcion']);
        $this->addOrderBy($name, ['iddiario'], 'code');
        $this->addOrderBy($name, ['descripcion'], 'description');
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
                $code = $this->request->request->get('code', '');
                if ($this->views['ListAsiento']->model->renumber($code)) {
                    $this->miniLog->notice($this->i18n->trans('renumber-accounting-ok'));
                }
                return true;

            default:
                return parent::execPreviousAction($action);
        }
    }
}
