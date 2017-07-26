<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Model;

/**
 * Controlador para la lista de series de facturación
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListSerie extends Base\ListController
{

    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        $this->addOrderBy('codserie', 'Código');
        $this->addOrderBy('descripcion');
        $this->addOrderBy('codejercicio', 'Ejercicio');

        $this->addFilterSelect('ejercicio', 'series', '', 'codejercicio');
        $this->addFilterCheckbox('siniva', 'Sin Impuesto', 'siniva');
    }

    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);

        // Load data with estructure data
        $where = $this->getWhere();
        $order = $this->getOrderBy($this->selectedOrderBy);
        $model = new Model\Serie();
        $this->count = $model->count($where);
        if ($this->count > 0) {
            $this->cursor = $model->all($where, $order);
        }
    }

    protected function getWhere()
    {
        $result = parent::getWhere();

        if ($this->query != '') {
            $fields = "descripcion|codserie|codcuenta";
            $result[] = new Base\DataBase\DataBaseWhere($fields, $this->query, "LIKE");
        }
        return $result;
    }

    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'Series';
        $pagedata['icon'] = 'fa-file-text';
        $pagedata['menu'] = 'contabilidad';
        return $pagedata;
    }

    protected function getColumns()
    {
        return [
            ['label' => 'Codigo', 'field' => 'codimpuesto', 'display' => 'left'],
            ['label' => 'Descripcion', 'field' => 'descripcion', 'display' => 'left'],
            ['label' => '% Iva', 'field' => 'iva', 'display' => 'right'],
            ['label' => '% Recargo', 'field' => 'recargo', 'display' => 'right'],
            ['label' => 'Sin Impuesto', 'field' => 'siniva', 'display' => 'center'],
            ['label' => 'Cuenta', 'field' => 'codcuenta', 'display' => 'left'],
            ['label' => 'Ejercicio', 'field' => 'codejercicio', 'display' => 'center'],
            ['label' => 'Núm. Factura', 'field' => 'numfactura', 'display' => 'right']
        ];
    }
}
