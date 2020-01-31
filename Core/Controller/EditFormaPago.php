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
use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Controller to edit a single item from the FormaPago model
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Artex Trading sa         <jcuello@artextrading.com>
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 */
class EditFormaPago extends EditController
{

    /**
     * Returns the model name.
     * 
     * @return string
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
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'payment-method';
        $data['icon'] = 'fas fa-credit-card';
        return $data;
    }

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

            default:
                return parent::autocompleteAction();
        }
    }

    /**
     * 
     * @param string $filterField
     *
     * @return array
     */
    protected function autocompleteWithFilter($filterField)
    {
        $results = [];
        $data = $this->requestGet(['field', 'source', 'fieldcode', 'fieldtitle', 'term', $filterField]);
        $fields = $data['fieldcode'] . '|' . $data['fieldtitle'];
        $where = [
            new DataBaseWhere($filterField, $data[$filterField]),
            new DataBaseWhere($fields, mb_strtolower($data['term'], 'UTF8'), 'LIKE')
        ];

        foreach ($this->codeModel->all($data['source'], $data['fieldcode'], $data['fieldtitle'], false, $where) as $row) {
            $results[] = ['key' => $row->code, 'value' => $row->description];
        }

        if (empty($results)) {
            $results[] = ['key' => null, 'value' => $this->toolBox()->i18n()->trans('no-value')];
        }
        return $results;
    }
}
