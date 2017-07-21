<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015       Pablo Peralta
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
 * Controlador para la lista de agencias de transportes
 *
 * @author Pablo Peralta
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class AgenciaTransporte extends Base\ListController
{
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        $this->fields = [
            ['label' => 'Codigo', 'field' => 'codtrans', 'display' => 'left'],
            ['label' => 'Nombre', 'field' => 'nombre', 'display' => 'left'],
            ['label' => 'Teléfono', 'field' => 'telefono', 'display' => 'left'],
            ['label' => 'Web', 'field' => 'web', 'display' => 'left'],
            ['label' => 'Activo', 'field' => 'activo', 'display' => 'left']
        ];

        $this->addOrderBy('codtrans', 'Código');
        $this->addOrderBy('nombre');

        $this->addFilterCheckbox('activo', 'Activo', '', TRUE);
    }

    public function publicCore(&$response)
    {
        parent::publicCore($response);
    }

    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);

        // Load data with estructure data
        $where = $this->getWhere();
        $order = $this->getOrderBy($this->selectedOrderBy);
        $model = new Model\AgenciaTransporte();
        $this->count = $model->count($where);
        if ($this->count > 0) {
            $this->cursor = $model->all($where, $order);
        }
    }

    protected function getWhere()
    {
        $result = parent::getWhere();

        if ($this->query != '') {
            $fields = "nombre|codtrans";
            $result[] = new Base\DataBase\DatabaseWhere($fields, $this->query, "LIKE");
        }
        return $result;
    }

    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['icon'] = 'fa-truck';
        $pagedata['menu'] = 'admin';
        return $pagedata;
    }
}
