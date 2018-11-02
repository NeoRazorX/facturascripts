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

use FacturaScripts\Core\Lib\ExtendedController;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\CodeModel;

/**
 * Controller to edit a single item from the FormaPago model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class EditFormaPago extends ExtendedController\EditController
{

    /**
     * Run the autocomplete action with exercise filter
     * Returns a JSON string for the searched values.
     *
     * @return array
     */
    protected function autocompleteAction(): array
    {
        $source = $this->request->get('source', '');
        switch ($source) {
            case 'cuentasbanco':
                return $this->autocompleteWithFilter('idempresa');

            case 'subcuentas':
                return $this->autocompleteWithFilter('codejercicio');

            case 'ejercicios':
                return $this->autocompleteWithFilter('idempresa');

          default:
              return parent::autocompleteAction();
        }
    }

    private function autocompleteWithFilter($filterField)
    {
        $results = [];
        $data = $this->requestGet(['field', 'source', 'fieldcode', 'fieldtitle', 'term', $filterField]);
        $fields = $data['fieldcode'] . '|' . $data['fieldtitle'];
        $where = [
            new DataBaseWhere($filterField, $data[$filterField]),
            new DataBaseWhere($fields, mb_strtolower($data['term'], 'UTF8'), 'LIKE')
        ];

        foreach (CodeModel::all($data['source'], $data['fieldcode'], $data['fieldtitle'], false, $where) as $row) {
            $results[] = ['key' => $row->code, 'value' => $row->description];
        }

        if (empty($results)) {
            $results[] = ['key' => null, 'value' => $this->i18n->trans('no-value')];
        }
        return $results;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->createViewExercises();
    }

    private function createViewExercises($viewName = 'EditFormaPagoEjercicio')
    {
        $this->addEditListView($viewName, 'FormaPagoEjercicio', 'exercises', 'fas fa-calendar-alt');
        $this->views[$viewName]->disableColumn('codpago');
    }

    /**
     * Returns the model name
     */
    public function getModelClassName()
    {
        return 'FormaPago';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'payment-method';
        $pagedata['menu'] = 'accounting';
        $pagedata['icon'] = 'fas fa-credit-card';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Load view data procedure
     *
     * @param string                      $viewName
     * @param ExtendedController\EditView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'FormaPagoEjercicio':
                $payment = $this->getViewModelValue('EditFormapago', 'codpago');
                $where = [new DataBaseWhere('codpago', $payment)];
                $view->loadData('', $where, ['idempresa' => 'ASC', 'codejercicio' => 'ASC']);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
